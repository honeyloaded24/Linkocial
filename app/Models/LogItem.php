<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'log_product_id',
        'content',
        'file_path',
        'file_name',
        'is_sold',
        'user_id',
        'log_order_id',
    ];

    protected $casts = [
        'is_sold' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(LogProduct::class, 'log_product_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(LogOrder::class, 'log_order_id');
    }
}
