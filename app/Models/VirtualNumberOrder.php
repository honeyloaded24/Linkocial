<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VirtualNumberOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'api_order_id',
        'phone_number',
        'country',
        'operator',
        'server',
        'product',
        'cost',
        'provider_cost',
        'provider_supplier_id',
        'provider_quote_usd',
        'provider_returned_operator',
        'status',
        'sms_text',
        'sms_code',
        'expires_at',
        'fivesim_id',
        'coupon_code',
        'discount',
    ];

    public function setFivesimIdAttribute($value): void
    {
        $this->attributes['api_order_id'] = $value;
    }

    public function getFivesimIdAttribute(): ?string
    {
        return $this->attributes['api_order_id'] ?? null;
    }

    protected $casts = [
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
