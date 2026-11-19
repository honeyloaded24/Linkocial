<?php

namespace App\Console\Commands;

use App\Models\AiToolOrder;
use App\Services\AlaricApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PollStaleAiToolOrders extends Command
{
    protected $signature = 'ai-tools:poll-stale-orders {--limit=100}';
    protected $description = 'Recover automatic AI Tools fulfillment polling for stale processing orders';

    public function handle(AlaricApiService $alaric): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $orders = AiToolOrder::query()
            ->where('status', AiToolOrder::STATUS_PROCESSING)
            ->whereNotNull('provider_order_id')
            ->where(function ($query) {
                $query->whereNull('last_polled_at')
                    ->orWhere('last_polled_at', '<=', now()->subMinute());
            })
            ->orderBy('last_polled_at')
            ->limit($limit)
            ->get();

        foreach ($orders as $order) {
            try {
                $order->livePollStatus($alaric);
                // livePollStatus updates last_polled_at when the provider responds.
                // Record the attempt even on transient failures to prevent a tight retry loop.
                if (!$order->fresh()->last_polled_at || $order->fresh()->last_polled_at->lt(now()->subMinute())) {
                    $order->update(['last_polled_at' => now()]);
                }
            } catch (\Throwable $e) {
                Log::warning('PollStaleAiToolOrders: poll failed', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
                $order->update(['last_polled_at' => now()]);
            }
        }

        $this->info("Polled {$orders->count()} stale AI Tools orders.");
        return self::SUCCESS;
    }
}
