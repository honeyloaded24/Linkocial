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
        'provider_key',
        'daisy_extra_activation_id',
        'daisy_extra_activation_status',
        'daisy_extra_activation_requested_at',
        'daisy_extra_activation_fee',
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

    protected $hidden = ['provider_key'];

    protected $appends = ['supports_resend'];

    public function getSupportsResendAttribute(): bool
    {
        return ($this->attributes['provider_key'] ?? null) !== 'daisysms';
    }

    protected $casts = [
        'expires_at' => 'datetime',
        'daisy_extra_activation_requested_at' => 'datetime',
        'daisy_extra_activation_fee' => 'decimal:4',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
