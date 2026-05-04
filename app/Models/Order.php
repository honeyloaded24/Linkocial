<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'service_id',
        'provider_id',
        'provider_order_id',
        'link',
        'quantity',
        'start_count',
        'remains',
        'charge',
        'provider_cost',
        'status',
        'is_refunded',
        'error_log',
    ];

    protected $casts = [
        'charge' => 'decimal:4',
        'provider_cost' => 'decimal:4',
        'start_count' => 'integer',
        'remains' => 'integer',
    ];

    /**
     * Get the logs for the order.
     */
    public function logs()
    {
        return $this->hasMany(OrderLog::class);
    }

    /**
     * Helper to log status changes.
     */
    public function logStatusChange($newStatus, $message = null, $payload = null)
    {
        return $this->logs()->create([
            'old_status' => $this->status,
            'new_status' => $newStatus,
            'message'    => $message,
            'payload'    => $payload,
        ]);
    }

    /**
     * Get the user that owns the order.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the service associated with the order.
     */
    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Get the provider that fulfilled the order.
     */
    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }
}
