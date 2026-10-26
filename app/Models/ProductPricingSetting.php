<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductPricingSetting extends Model
{
    use HasFactory;

    protected $table = 'product_pricing_settings';

    protected $fillable = [
        'product_key',
        'currency',
        'fix_rate',
        'pricing_mode',
        'percentage_markup',
        'fixed_markup',
        'min_selling_price',
        'min_profit',
        'rounding_rule',
    ];

    protected $casts = [
        'fix_rate' => 'decimal:4',
        'percentage_markup' => 'decimal:2',
        'fixed_markup' => 'decimal:2',
        'min_selling_price' => 'decimal:2',
        'min_profit' => 'decimal:2',
    ];

    /**
     * Get the active provider currency.
     */
    public function getCurrency(): string
    {
        return strtoupper($this->currency ?? 'USD');
    }

    /**
     * Get the active fix rate (exchange rate).
     */
    public function getFixRate(): float
    {
        return $this->fix_rate !== null ? (float) $this->fix_rate : 1500.0;
    }

    /**
     * Get the pricing mode ('percentage', 'fixed', 'combined').
     */
    public function getPricingMode(): string
    {
        return $this->pricing_mode ?? 'percentage';
    }

    /**
     * Get the percentage markup.
     */
    public function getPercentageMarkup(): float
    {
        return $this->percentage_markup !== null ? (float) $this->percentage_markup : 0.0;
    }

    /**
     * Get the fixed markup in NGN.
     */
    public function getFixedMarkup(): float
    {
        return $this->fixed_markup !== null ? (float) $this->fixed_markup : 0.0;
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
}
