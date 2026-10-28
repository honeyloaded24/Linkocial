<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use DomainException;

class GiftCardOrder extends Model
{
    use HasFactory;

    protected $table = 'gift_card_orders';

    public const STATUS_PENDING    = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED  = 'completed';
    public const STATUS_FAILED     = 'failed';
    public const STATUS_REFUNDED   = 'refunded';

    public const PROVIDER_PRESTMIT = 'prestmit';

    /**
     * Allowed lifecycle state transitions.
     * Prevents invalid state machine operations and prevents premature refunds.
     */
    protected const ALLOWED_TRANSITIONS = [
        self::STATUS_PENDING    => [self::STATUS_PROCESSING, self::STATUS_FAILED, self::STATUS_REFUNDED],
        self::STATUS_PROCESSING => [self::STATUS_COMPLETED, self::STATUS_FAILED, self::STATUS_REFUNDED],
        self::STATUS_FAILED     => [self::STATUS_REFUNDED],
        self::STATUS_COMPLETED  => [],
        self::STATUS_REFUNDED   => [],
    ];

    protected $fillable = [
        'user_id',
        'order_reference',
        'provider_reference',
        'provider',
        'brand_code',
        'brand_name',
        'country_code',
        'face_value',
        'face_value_currency',
        'amount_ngn',
        'provider_cost',
        'provider_currency',
        'status',
        'idempotency_key',
        'status_details',
        'completed_at',
        'failed_at',
        'refunded_at',
    ];

    protected function casts(): array
    {
        return [
            'face_value'    => 'decimal:2',
            'amount_ngn'    => 'decimal:2',
            'provider_cost' => 'decimal:4',
            'completed_at'  => 'datetime',
            'failed_at'     => 'datetime',
            'refunded_at'   => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (GiftCardOrder $order) {
            if (empty($order->order_reference)) {
                $order->order_reference = static::generateOrderReference();
            }

            if (empty($order->idempotency_key)) {
                $order->idempotency_key = (string) Str::uuid();
            }

            if (empty($order->status)) {
                $order->status = self::STATUS_PENDING;
            }
        });
    }

    /**
     * Generate a unique, cryptographically random internal order reference.
     * Format: GC-YYYYMMDD-[10 uppercase alphanumeric chars]
     */
    public static function generateOrderReference(): string
    {
        do {
            $ref = 'GC-' . date('Ymd') . '-' . strtoupper(Str::random(10));
        } while (static::where('order_reference', $ref)->exists());

        return $ref;
    }

    /**
     * Owning customer.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if a status transition is permitted.
     * Idempotent: transitioning to the current state is always safely permitted.
     */
    public function canTransitionTo(string $targetStatus): bool
    {
        if ($this->status === $targetStatus) {
            return true;
        }

        $allowed = self::ALLOWED_TRANSITIONS[$this->status] ?? [];
        return in_array($targetStatus, $allowed, true);
    }

    /**
     * Safely transition order to target lifecycle status.
     *
     * @throws DomainException if transition is disallowed.
     */
    public function transitionTo(string $targetStatus, ?string $details = null): bool
    {
        if (!$this->canTransitionTo($targetStatus)) {
            throw new DomainException("Invalid status transition from '{$this->status}' to '{$targetStatus}'.");
        }

        // Idempotent: already at target status
        if ($this->status === $targetStatus) {
            if ($details !== null) {
                $this->status_details = $details;
                $this->save();
            }
            return true;
        }

        $this->status = $targetStatus;

        if ($details !== null) {
            $this->status_details = $details;
        }

        if ($targetStatus === self::STATUS_COMPLETED) {
            $this->completed_at = now();
        } elseif ($targetStatus === self::STATUS_FAILED) {
            $this->failed_at = now();
        } elseif ($targetStatus === self::STATUS_REFUNDED) {
            $this->refunded_at = now();
        }

        return $this->save();
    }

    /**
     * Transition order to processing.
     */
    public function markAsProcessing(?string $providerRef = null, ?string $details = null): bool
    {
        if ($providerRef !== null) {
            $this->provider_reference = $providerRef;
        }
        return $this->transitionTo(self::STATUS_PROCESSING, $details);
    }

    /**
     * Transition order to completed.
     */
    public function markAsCompleted(?string $details = null): bool
    {
        return $this->transitionTo(self::STATUS_COMPLETED, $details);
    }

    /**
     * Transition order to failed.
     */
    public function markAsFailed(string $reason): bool
    {
        return $this->transitionTo(self::STATUS_FAILED, $reason);
    }

    /**
     * Transition order to refunded.
     */
    public function markAsRefunded(?string $reason = null): bool
    {
        return $this->transitionTo(self::STATUS_REFUNDED, $reason);
    }

    /**
     * Safely record a provider timeout without causing premature failure or unsafe refunds.
     * Leaves order in processing for automated or manual reconciliation.
     */
    public function handleTimeout(?string $details = null): bool
    {
        $message = $details ?: 'Provider request timed out; awaiting async verification.';
        
        if ($this->status === self::STATUS_PENDING) {
            return $this->markAsProcessing(null, $message);
        }

        $this->status_details = $message;
        return $this->save();
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isRefunded(): bool
    {
        return $this->status === self::STATUS_REFUNDED;
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_REFUNDED], true);
    }

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

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopeRefunded($query)
    {
        return $query->where('status', self::STATUS_REFUNDED);
    }
}
