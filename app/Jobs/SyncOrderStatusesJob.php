<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\Provider;
use App\Services\SmmProviderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Transaction;

class SyncOrderStatusesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(SmmProviderService $smmService): void
    {
        // Find orders that are not in a final state
        $activeOrders = Order::whereIn('status', ['pending', 'processing', 'in_progress', 'partial'])
            ->whereNotNull('provider_order_id')
            ->whereNotNull('provider_id')
            ->get();

        if ($activeOrders->isEmpty()) {
            return;
        }

        // Group orders by provider to minimize API calls
        $groupedOrders = $activeOrders->groupBy('provider_id');

        foreach ($groupedOrders as $providerId => $orders) {
            $provider = Provider::find($providerId);
            if (!$provider || !$provider->is_active) continue;

            foreach ($orders as $order) {
                $statusData = $smmService->getOrderStatus($provider, $order->provider_order_id);

                if ($statusData && isset($statusData['status'])) {
                    $newStatus = $this->mapProviderStatus($statusData['status']);
                    
                    DB::transaction(function () use ($order, $newStatus, $statusData) {
                        $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->first();
                        if (!$lockedOrder) return;

                        $lockedOrder->update([
                            'status' => $newStatus,
                            'start_count' => $statusData['start_count'] ?? $lockedOrder->start_count,
                            'remains' => $statusData['remains'] ?? $lockedOrder->remains,
                        ]);

                        // Provider-confirmed terminal failures/cancellations must refund once.
                        if (in_array($newStatus, ['failed', 'canceled'], true) && !$lockedOrder->is_refunded) {
                            $lockedUser = User::whereKey($lockedOrder->user_id)->lockForUpdate()->first();
                            if ($lockedUser && (float) $lockedOrder->charge > 0) {
                                $reference = 'REF-SMM-' . $lockedOrder->id;
                                if (!Transaction::where('reference', $reference)->exists()) {
                                    $lockedUser->addBalance((float) $lockedOrder->charge);
                                    Transaction::add($lockedUser->id, (float) $lockedOrder->charge, 'credit', 'refund', "Provider-confirmed refund for failed order #{$lockedOrder->id}", $reference, $lockedOrder->created_at);
                                }
                                $lockedOrder->update(['is_refunded' => true]);
                            }
                        }
                    });

                    Log::info("Order #{$order->id} synced. Status: {$newStatus}");
                }
            }
        }
    }

    /**
     * Map provider status string to our internal status
     */
    private function mapProviderStatus(string $providerStatus): string
    {
        $status = strtolower($providerStatus);
        
        return match ($status) {
            'pending' => 'pending',
            'processing' => 'processing',
            'inprogress', 'in progress' => 'in_progress',
            'completed' => 'completed',
            'partial' => 'partial',
            'canceled' => 'canceled',
            'failed', 'error' => 'failed',
            default => $status,
        };
    }
}
