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
        'status',
        'sms_text',
        'sms_code',
        'expires_at',
        'coupon_code',
        'discount',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
