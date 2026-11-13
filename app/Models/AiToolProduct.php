<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use App\Services\ProductPricingService;

class AiToolProduct extends Model
{
    use HasFactory;

    protected $table = 'ai_tool_products';

    protected $fillable = [
        'provider_product_id',
        'category_id',
        'name',
        'slug',
        'description',
        'price_usdt',
        'currency',
        'in_stock',
        'stock',
        'max_quantity',
        'fulfillment',
        'price_tiers',
        'is_active',
        'is_featured',
        'sort_order',
        'custom_markup_percentage',
        'custom_markup_fixed',
        'last_synced_at',
    ];

    protected $casts = [
        'price_usdt'               => 'decimal:8',
        'in_stock'                 => 'boolean',
        'stock'                    => 'integer',
        'max_quantity'             => 'integer',
        'price_tiers'              => 'array',
        'is_active'                => 'boolean',
        'is_featured'              => 'boolean',
        'sort_order'               => 'integer',
        'custom_markup_percentage' => 'decimal:4',
        'custom_markup_fixed'      => 'decimal:4',
        'last_synced_at'           => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name) . '-' . Str::random(5);
            }
        });
    }

    // ─── Relationships ─────────────────────────────────────────────────────────

    public function category(): BelongsTo
    {
        return $this->belongsTo(AiToolCategory::class, 'category_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(AiToolOrder::class, 'ai_tool_product_id');
    }

    // ─── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInStock($query)
    {
        return $query->where('in_stock', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    // ─── Pricing helpers ───────────────────────────────────────────────────────

    /**
     * Get provider cost in USDT for a given quantity, respecting price_tiers.
     */
    public function getPriceForQuantity(int $qty = 1): float
    {
        $tiers = $this->price_tiers;

        if (empty($tiers) || !is_array($tiers)) {
            return (float) $this->price_usdt;
        }

        // Sort descending by min_quantity — first match wins
        usort($tiers, fn($a, $b) => ($b['min_quantity'] ?? 0) <=> ($a['min_quantity'] ?? 0));

        foreach ($tiers as $tier) {
            $minQty = (int) ($tier['min_quantity'] ?? 1);
            if ($qty >= $minQty) {
                return (float) ($tier['price'] ?? $this->price_usdt);
            }
        }

        return (float) $this->price_usdt;
    }

    /**
     * Get the customer-facing selling price in NGN for a given quantity.
     * Returns the full pricing result array from ProductPricingService.
     */
    public function getSellingPriceNgn(int $qty = 1): array
    {
        $costUsdt       = $this->getPriceForQuantity($qty);
        $pricingService = app(ProductPricingService::class);

        $overrides = [];
        if ($this->custom_markup_percentage !== null && (float) $this->custom_markup_percentage > 0) {
            $overrides['percentage_markup'] = (float) $this->custom_markup_percentage;
            $overrides['pricing_mode']      = 'percentage';
        } elseif ($this->custom_markup_fixed !== null && (float) $this->custom_markup_fixed > 0) {
            $overrides['fixed_markup'] = (float) $this->custom_markup_fixed;
            $overrides['pricing_mode'] = 'fixed';
        }

        return $pricingService->calculateSellingPrice(
            $costUsdt,
            ProductPricingService::PRODUCT_AI_TOOLS,
            $overrides
        );
    }

    // ─── Availability ──────────────────────────────────────────────────────────

    /**
     * Check whether this product can be purchased right now.
     */
    public function isAvailableForPurchase(): bool
    {
        if (!$this->is_active || !$this->in_stock) {
            return false;
        }

        // Stock-type products need a positive stock count
        if ($this->fulfillment === 'stock' && ($this->stock === null || $this->stock <= 0)) {
            return false;
        }

        return true;
    }
}
