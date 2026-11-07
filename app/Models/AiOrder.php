<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiOrder extends Model
{
    use HasFactory;

    protected $table = 'ai_orders';

    protected $fillable = [
        'user_id',
        'ai_product_id',
        'provider_order_id',
        'idempotency_key',
        'product_name',
        'quantity',
        'unit_price',
        'total_price',
        'provider_cost',
        'status',
        'delivery_mode',
        'delivery_payload',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'quantity'      => 'integer',
            'unit_price'    => 'decimal:2',
            'total_price'   => 'decimal:2',
            'provider_cost' => 'decimal:2',
            'metadata'      => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(AiProduct::class, 'ai_product_id');
    }

    public function isFulfilled(): bool
    {
        return $this->status === 'fulfilled';
    }

    public function isPending(): bool
    {
        return in_array($this->status, ['pending', 'processing', 'awaiting_manual']);
    }

    public function isCancelled(): bool
    {
        return in_array($this->status, ['cancelled', 'refunded']);
    }

    /**
     * Parse delivery payload as structured credentials if stored as JSON, or return raw string.
     */
    public function getFormattedDeliveryAttribute(): string
    {
        if (empty($this->delivery_payload)) {
            return '';
        }

        $decoded = json_decode($this->delivery_payload, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $lines = [];
            foreach ($decoded as $k => $v) {
                if (is_scalar($v)) {
                    $lines[] = ucfirst(str_replace('_', ' ', $k)) . ": " . $v;
                }
            }
            return implode("\n", $lines) ?: $this->delivery_payload;
        }

        return $this->delivery_payload;
    }
}
