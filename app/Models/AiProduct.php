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
     * by extracting `resellerPriceKobo` from their stored raw API `metadata`.
     */
    public static function repairZeroPricesFromMetadata(): int
    {
        $repaired = 0;

        try {
            $zeroProducts = self::where(function ($q) {
                $q->where('wholesale_price_kobo', '<=', 0)
                  ->orWhere('wholesale_price', '<=', 0)
                  ->orWhere('retail_price', '<=', 0);
            })->get();

            if ($zeroProducts->isEmpty()) {
                return 0;
            }

            $globalMarkup = (float) Setting::get('ai_marketplace_markup', '20');

            foreach ($zeroProducts as $product) {
                $meta = $product->metadata;
                if (!is_array($meta) || empty($meta)) {
                    continue;
                }

                $priceKobo = \App\Services\AiPlugService::extractPriceKobo($meta);
                if ($priceKobo <= 0) {
                    continue;
                }

                $wholesaleNgn = round($priceKobo / 100, 2);
                $rawRetail = (float) $product->getRawOriginal('retail_price');
                $retailPrice = $rawRetail > 0 ? $rawRetail : round($wholesaleNgn * (1 + ($globalMarkup / 100)), 2);

                $updates = [
                    'wholesale_price_kobo' => $priceKobo,
                    'wholesale_price'      => $wholesaleNgn,
                    'retail_price'         => $retailPrice,
                ];

                if (empty($product->description) && !empty($meta['summary'])) {
                    $updates['description'] = (string) $meta['summary'];
                }

                if (array_key_exists('autoFulfill', $meta)) {
                    $updates['delivery_type'] = $meta['autoFulfill'] ? 'automatic' : 'manual';
                }

                $product->update($updates);
                $repaired++;
            }
        } catch (\Throwable $e) {
            // Safe fallback
        }

        return $repaired;
    }
}
