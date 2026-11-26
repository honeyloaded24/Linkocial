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
        'daisy_extra_sms_code',
        'daisy_extra_sms_text',
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
        'cancel_available_at',
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

    protected $appends = ['supports_resend', 'resend_fee_notice'];

    public function getSupportsResendAttribute(): bool
    {
        return true;
    }

    public function getResendFeeLabelAttribute(): string
    {
        return ($this->provider_key ?? null) === 'daisysms'
            ? 'Request Additional SMS · $0.20 converted'
            : 'Resend Code / Reuse';
    }

    public function getResendFeeNoticeAttribute(): ?string
    {
        return ($this->provider_key ?? null) === 'daisysms'
            ? 'USD $0.20 converted to your wallet currency is deducted when you request another SMS.'
            : null;
    }

    protected $casts = [
        'expires_at' => 'datetime',
        'cancel_available_at' => 'datetime',
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
