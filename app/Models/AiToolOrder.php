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
        'provider',
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
     * Deliver and fulfill the order with given credentials.
     *
     * @param string|array $credentials Can be multi-line string, single key, or array of item strings/objects
     * @param string|null  $instructions Optional notes/instructions
     */
    public function fulfillWithCredentials(string|array $credentials, ?string $instructions = null): bool
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($credentials, $instructions) {
            $itemsToSave = [];

            if (is_string($credentials)) {
                $trimmed = trim($credentials);
                $decoded = json_decode($trimmed, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $itemsToSave = array_is_list($decoded) ? $decoded : [$decoded];
                } else {
                    $lines = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $trimmed)));
                    $itemsToSave = !empty($lines) ? array_values($lines) : [$trimmed];
                }
            } elseif (is_array($credentials)) {
                $itemsToSave = array_is_list($credentials) ? $credentials : [$credentials];
            }

            // Remove any existing items if re-fulfilling
            $this->items()->delete();

            foreach ($itemsToSave as $index => $item) {
                $code = null;
                $details = null;

                if (is_string($item)) {
                    $code = trim($item);
                    if ($instructions) {
                        $details = ['instructions' => $instructions];
                    }
                } elseif (is_array($item)) {
                    $code = $item['code']
                        ?? $item['key']
                        ?? $item['license']
                        ?? $item['license_key']
                        ?? $item['account']
                        ?? $item['login']
                        ?? $item['credential']
                        ?? $item['value']
                        ?? (isset($item['email'], $item['password']) ? "{$item['email']} | {$item['password']}" : null)
                        ?? (isset($item['username'], $item['password']) ? "{$item['username']} | {$item['password']}" : null);

                    if (!$code) {
                        $code = is_scalar($item) ? (string) $item : json_encode($item);
                    }

                    $details = $item;
                    if ($instructions) {
                        $details['instructions'] = $instructions;
                    }
                }

                if (empty($code)) {
                    $code = 'Credentials provided (see details)';
                }

                AiToolOrderItem::create([
                    'ai_tool_order_id' => $this->id,
                    'item_index'       => (int) $index,
                    'code'             => (string) $code,
                    'details'          => $details ? (is_array($details) ? json_encode($details) : (string) $details) : null,
                ]);
            }

            $this->update([
                'status'         => self::STATUS_COMPLETED,
                'completed_at'   => now(),
                'failure_reason' => null,
            ]);

            return true;
        });
    }

    /**
     * Robust extraction of delivered credentials from provider response.
     */
    public function parseAndDeliverFromProviderResponse(array $response): bool
    {
        $data = $response['data'] ?? $response['order'] ?? $response;

        $items = [];
        // PixelSub returns delivered credentials nested in order.items[].delivered.
        if (!empty($data['items']) && is_array($data['items'])) {
            $delivered = [];
            foreach ($data['items'] as $providerItem) {
                if (is_array($providerItem) && array_key_exists('delivered', $providerItem) && $providerItem['delivered'] !== null && $providerItem['delivered'] !== '') {
                    $delivered[] = $providerItem['delivered'];
                }
            }
            if (!empty($delivered)) {
                $items = $delivered;
            }
        }
        if (empty($items) && !empty($data['items']) && is_array($data['items'])) {
            $items = $data['items'];
        } elseif (!empty($data['credentials']) && is_array($data['credentials'])) {
            $items = $data['credentials'];
        } elseif (!empty($data['accounts']) && is_array($data['accounts'])) {
            $items = $data['accounts'];
        } elseif (!empty($data['code'])) {
            $items = [$data['code']];
        } elseif (!empty($data['license_key'])) {
            $items = [$data['license_key']];
        } elseif (!empty($data['delivery'])) {
            $items = is_array($data['delivery']) ? $data['delivery'] : [$data['delivery']];
        } elseif (!empty($data['delivery_payload'])) {
            $items = [$data['delivery_payload']];
        }

        if (!empty($items)) {
            $this->fulfillWithCredentials($items);
            return true;
        }

        $status = strtoupper((string) ($data['status'] ?? $response['status'] ?? ''));
        if ($status === 'COMPLETED') {
            $this->update([
                'status'       => self::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);
            return true;
        }

        return false;
    }

    /**
     * Refund the customer for this order.
     */
    public function refundCustomer(?string $reason = null): bool
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($reason) {
            $order = self::query()->lockForUpdate()->findOrFail($this->id);

            if ($order->status === self::STATUS_REFUNDED || $order->status === self::STATUS_COMPLETED) {
                return false;
            }

            $user = User::query()->lockForUpdate()->find($order->user_id);
            if ($user && (float) $order->total_paid_ngn > 0) {
                $user->addBalance((float) $order->total_paid_ngn);

                Transaction::add(
                    $user->id,
                    (float) $order->total_paid_ngn,
                    'credit',
                    'ai_tools_refund',
                    "Refund — AI TOOLS order #{$order->order_number}" . ($reason ? " ({$reason})" : ''),
                    'REF-AITOOLS-' . $order->id,
                    $order->created_at
                );
            }

            $order->update([
                'status'         => self::STATUS_REFUNDED,
                'refunded_at'    => now(),
                'failure_reason' => $reason ?? 'Refunded by administrator',
            ]);

            $this->refresh();
            return true;
        });
    }

    /**
     * Live poll the provider for order status & delivered credentials.
     */
    public function livePollStatus(\App\Services\AlaricApiService|\App\Services\PixelSubApiService $providerService): array
    {
        if (empty($this->provider_order_id) || $this->isTerminal()) {
            return ['success' => false, 'message' => 'Order is terminal or missing provider order ID'];
        }

        try {
            $result = $providerService->getOrder($this->provider_order_id);
            if (!($result['success'] ?? false)) {
                return $result;
            }

            $data = $result['data'] ?? [];
            $providerStatus = strtoupper((string) ($data['status'] ?? ''));

            $this->update([
                'provider_status'   => $providerStatus,
                'provider_response' => $data,
                'last_polled_at'    => now(),
            ]);

            if ($providerStatus === 'COMPLETED' || !empty($data['items']) || !empty($data['credentials']) || !empty($data['code'])) {
                $this->parseAndDeliverFromProviderResponse($data);
                return ['success' => true, 'status' => 'completed', 'message' => 'Order completed and credentials delivered.'];
            }

            if ($providerStatus === 'REFUNDED') {
                $this->refundCustomer('Provider cancelled/refunded the order.');
                return ['success' => true, 'status' => 'refunded', 'message' => 'Provider refunded this order.'];
            }

            if ($providerStatus === 'FAILED') {
                $this->update([
                    'status'         => self::STATUS_FAILED,
                    'failure_reason' => $data['failure_reason'] ?? $data['message'] ?? 'Provider failed to fulfill order.',
                ]);
                return ['success' => true, 'status' => 'failed', 'message' => 'Provider order failed.'];
            }

            if ($providerStatus === 'UNDER_REVIEW') {
                $this->update([
                    'status'         => self::STATUS_UNDER_REVIEW,
                    'failure_reason' => 'Provider placed order under review.',
                ]);
                return ['success' => true, 'status' => 'under_review', 'message' => 'Order is under review.'];
            }

            return ['success' => true, 'status' => 'processing', 'message' => 'Order is still processing with provider.'];
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('AiToolOrder::livePollStatus failed: ' . $e->getMessage(), ['order_id' => $this->id]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
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
