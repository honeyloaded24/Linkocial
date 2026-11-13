<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class AiToolOrder extends Model
{
    use HasFactory;

    protected $table = 'ai_tool_orders';

    // ─── Status constants ──────────────────────────────────────────────────────
    public const STATUS_PENDING      = 'pending';
    public const STATUS_PROCESSING   = 'processing';
    public const STATUS_COMPLETED    = 'completed';
    public const STATUS_REFUNDED     = 'refunded';
    public const STATUS_FAILED       = 'failed';
    public const STATUS_UNDER_REVIEW = 'under_review';

    protected $fillable = [
        'user_id',
        'ai_tool_product_id',
        'order_number',
        'idempotency_key',
        'provider_order_id',
        'provider_product_id',
        'product_name',
        'status',
        'provider_status',
        'quantity',
        'unit_price_usdt',
        'total_price_usdt',
        'exchange_rate',
        'unit_price_ngn',
        'total_paid_ngn',
        'provider_cost_ngn',
        'profit_ngn',
        'max_total_usdt',
        'provider_response',
        'failure_reason',
        'error_message',
        'retry_count',
        'last_polled_at',
        'completed_at',
        'refunded_at',
    ];

    protected $casts = [
        'unit_price_usdt'   => 'decimal:8',
        'total_price_usdt'  => 'decimal:8',
        'exchange_rate'     => 'decimal:4',
        'unit_price_ngn'    => 'decimal:2',
        'total_paid_ngn'    => 'decimal:2',
        'provider_cost_ngn' => 'decimal:2',
        'profit_ngn'        => 'decimal:2',
        'max_total_usdt'    => 'decimal:8',
        'provider_response' => 'array',
        'retry_count'       => 'integer',
        'last_polled_at'    => 'datetime',
        'completed_at'      => 'datetime',
        'refunded_at'       => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = 'AIT-' . strtoupper(Str::random(10));
            }
        });
    }

    // ─── Relationships ─────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(AiToolProduct::class, 'ai_tool_product_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(AiToolOrderItem::class, 'ai_tool_order_id');
    }

    // ─── Scopes ────────────────────────────────────────────────────────────────

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', self::STATUS_PROCESSING);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Returns true if the order has reached a terminal state.
     */
    public function isTerminal(): bool
    {
        return in_array($this->status, [
            self::STATUS_COMPLETED,
            self::STATUS_REFUNDED,
            self::STATUS_FAILED,
            self::STATUS_UNDER_REVIEW,
        ]);
    }

    /**
     * Bootstrap CSS badge colour for status display.
     */
    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_COMPLETED    => 'success',
            self::STATUS_PROCESSING   => 'warning',
            self::STATUS_PENDING      => 'secondary',
            self::STATUS_REFUNDED     => 'info',
            self::STATUS_FAILED       => 'danger',
            self::STATUS_UNDER_REVIEW => 'dark',
            default                   => 'secondary',
        };
    }

    /**
     * Human-readable status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_COMPLETED    => 'Completed',
            self::STATUS_PROCESSING   => 'Processing',
            self::STATUS_PENDING      => 'Pending',
            self::STATUS_REFUNDED     => 'Refunded',
            self::STATUS_FAILED       => 'Failed',
            self::STATUS_UNDER_REVIEW => 'Under Review',
            default                   => ucfirst($this->status),
        };
    }
}
