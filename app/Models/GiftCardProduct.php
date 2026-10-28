<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GiftCardProduct extends Model
{
    use HasFactory;

    protected $table = 'gift_card_products';

    protected $fillable = [
        'brand_code',
        'brand_name',
        'country_code',
        'currency',
        'min_face_value',
        'max_face_value',
        'fixed_denominations',
        'icon',
        'is_active',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'min_face_value'      => 'decimal:2',
            'max_face_value'      => 'decimal:2',
            'fixed_denominations' => 'array',
            'is_active'           => 'boolean',
            'metadata'            => 'array',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCountry($query, string $countryCode)
    {
        return $query->where('country_code', strtoupper($countryCode));
    }
}
