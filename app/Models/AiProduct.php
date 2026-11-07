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
     * Compute retail selling price applying markup if retail_price is not custom set.
     */
    public function getEffectiveSellingPriceAttribute(): float
    {
        if ($this->retail_price > 0) {
            return (float) $this->retail_price;
        }

        $markupPercent = (float) Setting::get('ai_marketplace_markup', '20');
        $wholesale = (float) $this->wholesale_price;

        return round($wholesale * (1 + ($markupPercent / 100)), 2);
    }
}
