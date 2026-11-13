<?php

namespace App\Jobs;

use App\Models\AiToolOrder;
use App\Models\AiToolOrderItem;
use App\Models\Transaction;
use App\Services\AlaricApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PollAiToolOrderStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 60;

    /** Maximum number of polling attempts before flagging for review (~10 min at 10s intervals) */
    private const MAX_RETRIES  = 60;
    /** Seconds between each poll */
    private const POLL_DELAY_S = 10;

    public function __construct(public readonly AiToolOrder $order) {}

    public function handle(AlaricApiService $alaricService): void
    {
        $order = $this->order->fresh();

        // Skip if order no longer exists or is already in a terminal state
        if (!$order || $order->isTerminal()) {
            return;
        }

        if (empty($order->provider_order_id)) {
            Log::warning('PollAiToolOrderStatus: no provider_order_id — cannot poll', [
                'order_id' => $order->id,
            ]);
            return;
        }

        // Track poll attempt and timestamp
        $order->increment('retry_count');
        $order->update(['last_polled_at' => now()]);

        // Guard against infinite polling
        if ($order->retry_count > self::MAX_RETRIES) {
            $order->update([
                'status'         => AiToolOrder::STATUS_UNDER_REVIEW,
                'failure_reason' => 'Maximum polling retries reached. Flagged for manual review.',
            ]);
            Log::warning('PollAiToolOrderStatus: max retries exceeded', ['order_id' => $order->id]);
            return;
        }

        $result = $alaricService->getOrder($order->provider_order_id);

        if (!($result['success'] ?? false)) {
            Log::warning('PollAiToolOrderStatus: getOrder API call failed', [
                'order_id' => $order->id,
                'error'    => $result['error_message'] ?? 'unknown',
            ]);
            // Network/transient error — re-queue to try again
            static::dispatch($order)->delay(now()->addSeconds(self::POLL_DELAY_S));
            return;
        }

        $data           = $result['data'] ?? [];
        $providerStatus = strtoupper((string) ($data['status'] ?? ''));

        $order->update([
            'provider_status'   => $providerStatus,
            'provider_response' => $data,
        ]);

        match ($providerStatus) {
            'COMPLETED'    => $this->handleCompleted($order, $data),
            'REFUNDED'     => $this->handleRefunded($order),
            'FAILED'       => $this->handleFailed($order, $data),
            'UNDER_REVIEW' => $this->handleUnderReview($order, $data),
            default        => $this->handleProcessing($order), // PROCESSING or unknown
        };
    }

    // ─── Status handlers ───────────────────────────────────────────────────────

    private function handleCompleted(AiToolOrder $order, array $data): void
    {
        DB::transaction(function () use ($order, $data) {
            $items = $data['items'] ?? [];

            foreach ($items as $index => $item) {
                AiToolOrderItem::create([
                    'ai_tool_order_id' => $order->id,
                    'item_index'       => $index,
                    'code'             => $item['code'] ?? null,
                    'details'          => is_array($item) ? json_encode($item) : ($item['details'] ?? null),
                ]);
            }

            $order->update([
                'status'       => AiToolOrder::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);
        });

        Log::info('PollAiToolOrderStatus: order completed', [
            'order_id'    => $order->id,
            'items_count' => count($data['items'] ?? []),
        ]);
    }

    private function handleProcessing(AiToolOrder $order): void
    {
        $order->update(['status' => AiToolOrder::STATUS_PROCESSING]);

        if ($order->retry_count < self::MAX_RETRIES) {
            static::dispatch($order)->delay(now()->addSeconds(self::POLL_DELAY_S));
        } else {
            $order->update([
                'status'         => AiToolOrder::STATUS_UNDER_REVIEW,
                'failure_reason' => 'Order remained in PROCESSING state too long.',
            ]);
        }
    }

    private function handleRefunded(AiToolOrder $order): void
    {
        DB::transaction(function () use ($order) {
            $user = $order->user;

            if ($user) {
                $user->addBalance((float) $order->total_paid_ngn);

                Transaction::add(
                    $user->id,
                    (float) $order->total_paid_ngn,
                    'credit',
                    'ai_tools_refund',
                    "AI TOOLS refund for order {$order->order_number}"
                );
            }

            $order->update([
                'status'      => AiToolOrder::STATUS_REFUNDED,
                'refunded_at' => now(),
            ]);
        });

        Log::info('PollAiToolOrderStatus: order refunded — wallet credited', ['order_id' => $order->id]);
    }

    private function handleFailed(AiToolOrder $order, array $data): void
    {
        $reason = $data['failure_reason'] ?? $data['message'] ?? 'Provider order failed.';

        // Do NOT auto-refund FAILED — flag for admin investigation
        $order->update([
            'status'         => AiToolOrder::STATUS_FAILED,
            'failure_reason' => $reason,
        ]);

        Log::warning('PollAiToolOrderStatus: order FAILED — requires admin review', [
            'order_id' => $order->id,
            'reason'   => $reason,
        ]);
    }

    private function handleUnderReview(AiToolOrder $order, array $data): void
    {
        $order->update([
            'status'         => AiToolOrder::STATUS_UNDER_REVIEW,
            'failure_reason' => 'Provider flagged for review: ' . ($data['message'] ?? 'No reason given.'),
        ]);

        Log::warning('PollAiToolOrderStatus: order under provider review', ['order_id' => $order->id]);
    }

    // ─── Job failure handler ───────────────────────────────────────────────────

    public function failed(\Throwable $exception): void
    {
        Log::error('PollAiToolOrderStatus: job permanently failed', [
            'order_id' => $this->order->id ?? null,
            'error'    => $exception->getMessage(),
        ]);

        if ($this->order && !$this->order->isTerminal()) {
            $this->order->update([
                'status'         => AiToolOrder::STATUS_UNDER_REVIEW,
                'failure_reason' => 'Background polling job failed: ' . $exception->getMessage(),
            ]);
        }
    }
}
