<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Provider extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'api_url',
        'api_key',
        'is_active',
        'currency',
        'pricing_mode',
        'percentage_markup',
        'fixed_markup_per_k',
        'min_selling_price',
        'min_profit',
        'rounding_rule',
        'last_synced_at',
        'last_sync_status',
        'last_sync_message',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'percentage_markup' => 'decimal:2',
        'fixed_markup_per_k' => 'decimal:2',
        'min_selling_price' => 'decimal:2',
        'min_profit' => 'decimal:2',
        'last_synced_at' => 'datetime',
    ];

    /**
     * Get the service mappings associated with the provider.
     */
    public function mappings()
    {
        return $this->hasMany(ProviderServiceMapping::class);
    }

    /**
     * Get the configured pricing mode with safe fallback.
     */
    public function getPricingMode(): string
    {
        return $this->pricing_mode ?? 'percentage';
    }

    /**
     * Get the percentage markup with safe default (30%).
     */
    public function getPercentageMarkup(): float
    {
        return $this->percentage_markup !== null ? (float) $this->percentage_markup : 30.0;
    }

    /**
     * Get the fixed markup per 1,000 units.
     */
    public function getFixedMarkupPerK(): float
    {
        return $this->fixed_markup_per_k !== null ? (float) $this->fixed_markup_per_k : 0.0;
    }

    /**
     * Get minimum selling price floor in NGN.
     */
    public function getMinSellingPrice(): ?float
    {
        return $this->min_selling_price !== null ? (float) $this->min_selling_price : null;
    }

    /**
     * Get minimum profit floor in NGN.
     */
    public function getMinProfit(): ?float
    {
        return $this->min_profit !== null ? (float) $this->min_profit : null;
    }

    /**
     * Get rounding rule (2_decimal, whole_naira, nearest_10).
     */
    public function getRoundingRule(): string
    {
        return $this->rounding_rule ?? '2_decimal';
    }

    /**
     * Get provider wholesale pricing currency.
     */
    public function getCurrency(): string
    {
        return strtoupper($this->currency ?? 'USD');
    }
}
