<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderLog extends Model
{
    protected $fillable = [
        'order_id',
        'old_status',
        'new_status',
        'message',
        'payload',
    ];

    protected $casts = [
        'payload' => 'json',
    ];

    /**
     * Get the order that owns the log.
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
