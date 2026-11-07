<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiProduct extends Model
{
    use HasFactory;

    protected $table = 'ai_products';

    protected $fillable = [
        'provider_product_id',
        'name',
        'category',
        'description',
        'icon',
        'wholesale_price_kobo',
        'wholesale_price',
        'retail_price',
        'in_stock',
        'stock_count',
        'delivery_type',
        'is_active',
        'is_hot',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'wholesale_price_kobo' => 'integer',
            'wholesale_price'      => 'decimal:2',
            'retail_price'         => 'decimal:2',
            'in_stock'             => 'boolean',
            'stock_count'          => 'integer',
            'is_active'            => 'boolean',
            'is_hot'               => 'boolean',
            'metadata'             => 'array',
        ];
    }

    public function orders()
    {
        return $this->hasMany(AiOrder::class, 'ai_product_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInStock($query)
    {
        return $query->where('in_stock', true);
    }

    /**
     * Get the exact available stock count if tracked by the provider, or null if unlimited/on-demand.
     */
    public function getAvailableStockAttribute(): ?int
    {
        $meta = $this->metadata;
        if (is_array($meta)) {
            foreach (['stock', 'stockCount', 'stock_count', 'availableStock', 'available_stock'] as $key) {
                if (array_key_exists($key, $meta)) {
                    if ($meta[$key] === null) {
                        return null;
                    }
                    if (is_numeric($meta[$key])) {
                        return max(0, (int) $meta[$key]);
                    }
                }
            }
        }

        $rawStock = $this->attributes['stock_count'] ?? null;
        if ($rawStock !== null && (int) $rawStock !== 999) {
            return max(0, (int) $rawStock);
        }

        $rawInStock = (bool) ($this->attributes['in_stock'] ?? true);
        return $rawInStock ? null : 0;
    }

    /**
     * Determine whether the product is currently in stock (false if provider stock count is 0).
     */
    public function getInStockAttribute($value): bool
    {
        if (!$value) {
            return false;
        }

        $available = $this->available_stock;
        if ($available !== null && $available <= 0) {
            return false;
        }

        return true;
    }

    /**
     * Get wholesale price in kobo, falling back to stored metadata (e.g. resellerPriceKobo) if column is 0.
     */
    public function getWholesalePriceKoboAttribute($value): int
    {
        $kobo = (int) $value;
        if ($kobo > 0) {
            return $kobo;
        }

        $meta = $this->metadata;
        if (is_array($meta) && !empty($meta)) {
            return \App\Services\AiPlugService::extractPriceKobo($meta);
        }

        return 0;
    }

    /**
     * Get wholesale price in NGN, falling back to wholesale_price_kobo / metadata if column is 0.
     */
    public function getWholesalePriceAttribute($value): float
    {
        $price = (float) $value;
        if ($price > 0) {
            return round($price, 2);
        }

        $kobo = $this->wholesale_price_kobo;
        if ($kobo > 0) {
            return round($kobo / 100, 2);
        }

        return 0.0;
    }

    /**
     * Compute retail selling price applying markup if retail_price is not custom set.
     */
    public function getEffectiveSellingPriceAttribute(): float
    {
        $retail = (float) ($this->attributes['retail_price'] ?? 0);
        if ($retail > 0) {
            return round($retail, 2);
        }

        $markupPercent = 20.0;
        try {
            $markupPercent = (float) Setting::get('ai_marketplace_markup', '20');
        } catch (\Throwable $e) {
            $markupPercent = 20.0;
        }

        $wholesale = (float) $this->wholesale_price;

        return round($wholesale * (1 + ($markupPercent / 100)), 2);
    }

    /**
     * Automatically backfill any AI products in the database that were synced with 0 kobo
     * or default 999 stock by extracting `resellerPriceKobo` and `stock` from their stored `metadata`.
     */
    public static function repairZeroPricesFromMetadata(): int
    {
        $repaired = 0;

        try {
            $candidates = self::where(function ($q) {
                $q->where('wholesale_price_kobo', '<=', 0)
                  ->orWhere('wholesale_price', '<=', 0)
                  ->orWhere('retail_price', '<=', 0)
                  ->orWhere('stock_count', 999);
            })->get();

            if ($candidates->isEmpty()) {
                return 0;
            }

            $globalMarkup = (float) Setting::get('ai_marketplace_markup', '20');

            foreach ($candidates as $product) {
                $meta = $product->metadata;
                if (!is_array($meta) || empty($meta)) {
                    continue;
                }

                $updates = [];

                $priceKobo = \App\Services\AiPlugService::extractPriceKobo($meta);
                if ($priceKobo > 0 && ((int) $product->getRawOriginal('wholesale_price_kobo') <= 0 || (float) $product->getRawOriginal('wholesale_price') <= 0 || (float) $product->getRawOriginal('retail_price') <= 0)) {
                    $wholesaleNgn = round($priceKobo / 100, 2);
                    $rawRetail = (float) $product->getRawOriginal('retail_price');
                    $retailPrice = $rawRetail > 0 ? $rawRetail : round($wholesaleNgn * (1 + ($globalMarkup / 100)), 2);

                    $updates['wholesale_price_kobo'] = $priceKobo;
                    $updates['wholesale_price'] = $wholesaleNgn;
                    $updates['retail_price'] = $retailPrice;
                }

                if (array_key_exists('stock', $meta) && $meta['stock'] !== null && is_numeric($meta['stock'])) {
                    $exactStock = max(0, (int) $meta['stock']);
                    if ((int) $product->getRawOriginal('stock_count') !== $exactStock) {
                        $updates['stock_count'] = $exactStock;
                    }
                    if ($exactStock === 0 && (bool) $product->getRawOriginal('in_stock')) {
                        $updates['in_stock'] = false;
                    }
                }

                if (empty($product->description) && !empty($meta['summary'])) {
                    $updates['description'] = (string) $meta['summary'];
                }

                if (array_key_exists('autoFulfill', $meta)) {
                    $expectedDelivery = $meta['autoFulfill'] ? 'automatic' : 'manual';
                    if ($product->delivery_type !== $expectedDelivery) {
                        $updates['delivery_type'] = $expectedDelivery;
                    }
                }

                if (!empty($updates)) {
                    $product->update($updates);
                    $repaired++;
                }
            }
        } catch (\Throwable $e) {
            // Safe fallback
        }

        return $repaired;
    }
}
