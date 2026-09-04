<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogOrder extends Model
{
    protected $fillable = [
        'user_id', 'log_product_id', 'api_product_key', 'product_name',
        'quantity', 'unit_cost', 'total_cost', 'external_order_id', 
        'status', 'delivered_items'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(LogProduct::class, 'log_product_id');
    }
}
