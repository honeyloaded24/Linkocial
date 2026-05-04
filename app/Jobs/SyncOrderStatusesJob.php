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
                    
                    $order->update([
                        'status' => $newStatus,
                        'start_count' => $statusData['start_count'] ?? $order->start_count,
                        'remains' => $statusData['remains'] ?? $order->remains,
                    ]);

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
