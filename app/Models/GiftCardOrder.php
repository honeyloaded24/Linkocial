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
     * Concurrency-safe: executes inside a DB transaction with row-level locking (lockForUpdate)
     * and validates against the freshly reloaded database state.
     *
     * @throws DomainException if transition is disallowed.
     */
    public function transitionTo(string $targetStatus, ?string $details = null, ?string $providerRef = null): bool
    {
        // Unpersisted instance fallback (e.g. before initial DB save or in unit tests)
        if (!$this->exists) {
            if (!$this->canTransitionTo($targetStatus)) {
                throw new DomainException("Invalid status transition from '{$this->status}' to '{$targetStatus}'.");
            }
            if ($this->status === $targetStatus) {
                if ($details !== null) {
                    $this->status_details = $details;
                }
                if ($providerRef !== null) {
                    $this->provider_reference = $providerRef;
                }
                return true;
            }
            $this->status = $targetStatus;
            if ($details !== null) {
                $this->status_details = $details;
            }
            if ($providerRef !== null) {
                $this->provider_reference = $providerRef;
            }
            $this->stampLifecycleTime($targetStatus);
            return true;
        }

        // Persisted model: execute inside a database transaction with pessimistic row-locking
        return \Illuminate\Support\Facades\DB::transaction(function () use ($targetStatus, $details, $providerRef) {
            /** @var static|null $lockedOrder */
            $lockedOrder = static::where('id', $this->id)->lockForUpdate()->first();

            if (!$lockedOrder) {
                throw new DomainException("GiftCardOrder #{$this->id} not found in database for status transition.");
            }

            // Validate against the freshly locked database status, preventing stale in-memory models from racing
            if (!$lockedOrder->canTransitionTo($targetStatus)) {
                $this->refresh();
                throw new DomainException("Invalid status transition from '{$lockedOrder->status}' to '{$targetStatus}'.");
            }

            // Idempotent: already at target status in the database
            if ($lockedOrder->status === $targetStatus) {
                $updated = false;
                if ($details !== null && $lockedOrder->status_details !== $details) {
                    $lockedOrder->status_details = $details;
                    $updated = true;
                }
                if ($providerRef !== null && $lockedOrder->provider_reference !== $providerRef) {
                    $lockedOrder->provider_reference = $providerRef;
                    $updated = true;
                }
                if ($updated) {
                    $lockedOrder->save();
                }
                $this->refresh();
                return true;
            }

            $lockedOrder->status = $targetStatus;

            if ($details !== null) {
                $lockedOrder->status_details = $details;
            }

            if ($providerRef !== null) {
                $lockedOrder->provider_reference = $providerRef;
            }

            $lockedOrder->stampLifecycleTime($targetStatus);
            $lockedOrder->save();

            // Synchronize the current calling model instance with the committed database state
            $this->refresh();
            return true;
        });
    }

    /**
     * Stamp the appropriate lifecycle timestamp.
     */
    protected function stampLifecycleTime(string $targetStatus): void
    {
        $now = now();
        if ($targetStatus === self::STATUS_COMPLETED && empty($this->completed_at)) {
            $this->completed_at = $now;
        } elseif ($targetStatus === self::STATUS_FAILED && empty($this->failed_at)) {
            $this->failed_at = $now;
        } elseif ($targetStatus === self::STATUS_REFUNDED && empty($this->refunded_at)) {
            $this->refunded_at = $now;
        }
    }

    /**
     * Transition order to processing atomically with optional provider reference.
     */
    public function markAsProcessing(?string $providerRef = null, ?string $details = null): bool
    {
        return $this->transitionTo(self::STATUS_PROCESSING, $details, $providerRef);
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
        
        if (!$this->exists) {
            if ($this->status === self::STATUS_PENDING) {
                return $this->markAsProcessing(null, $message);
            }
            $this->status_details = $message;
            return true;
        }

        return \Illuminate\Support\Facades\DB::transaction(function () use ($message) {
            /** @var static|null $lockedOrder */
            $lockedOrder = static::where('id', $this->id)->lockForUpdate()->first();
            if (!$lockedOrder) {
                return false;
            }

            // If pending, safely transition to processing
            if ($lockedOrder->status === self::STATUS_PENDING) {
                $lockedOrder->status = self::STATUS_PROCESSING;
                $lockedOrder->status_details = $message;
                $lockedOrder->save();
            } elseif (!$lockedOrder->isTerminal()) {
                // If already processing or failed, record timeout details without overriding status or terminal states
                $lockedOrder->status_details = $message;
                $lockedOrder->save();
            }

            $this->refresh();
            return true;
        });
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
