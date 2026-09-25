<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Services\SmmProviderService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckOrderStatuses extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'smm:check-orders';

    /**
     * The console command description.
     */
    protected $description = 'Check the status of pending/processing orders with SMM providers';

    /**
     * Execute the console command.
     */
    public function handle(SmmProviderService $smmService)
    {
        $this->info('Starting Order Status Check...');

        // Fetch orders that are not in a final state
        $activeOrders = Order::whereIn('status', ['pending', 'processing', 'in_progress'])
                             ->whereNotNull('provider_order_id')
                             ->with(['provider', 'user'])
                             ->get();

        if ($activeOrders->isEmpty()) {
            $this->info('No active orders to check.');
            return;
        }

        $completedCount = 0;
        $refundedCount = 0;

        /** @var \App\Models\Order $order */
        foreach ($activeOrders as $order) {
            $provider = $order->provider;

            if (!$provider || !$provider->is_active) {
                continue;
            }

            $statusData = $smmService->getOrderStatus($provider, $order->provider_order_id);

            if ($statusData && isset($statusData['status'])) {
                // Typical SMM statuses: Pending, In progress, Completed, Partial, Canceled, Processing
                // We normalize space to underscore for high-authority system consistency
                $newStatus = strtolower(str_replace(' ', '_', $statusData['status']));
                
                // Capture Start Count if not already set and provided by API
                if (is_null($order->start_count) && isset($statusData['start_count']) && (int)$statusData['start_count'] > 0) {
                    $order->update(['start_count' => (int)$statusData['start_count']]);
                    $order->logStatusChange($order->status, "Captured start count: " . $statusData['start_count']);
                }

                // Update remains if provided
                if (isset($statusData['remains'])) {
                    $order->update(['remains' => (int)$statusData['remains']]);
                }

                // If the status changed
                if ($newStatus !== $order->status) {
                    DB::transaction(function () use ($order, $newStatus, $statusData, &$completedCount, &$refundedCount) {
                        
                        $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->first();
                        if (!$lockedOrder) {
                            return;
                        }

                        $user = $lockedOrder->user;
                        $oldStatus = $lockedOrder->status;

                        if ($newStatus === 'completed') {
                            $lockedOrder->update(['status' => 'completed']);
                            $lockedOrder->logStatusChange('completed', "Order marked as completed by provider.", $statusData);
                            $completedCount++;
                        } 
                        elseif ($newStatus === 'canceled') {
                            if (!$lockedOrder->is_refunded) {
                                $lockedUser = \App\Models\User::where('id', $user->id)->lockForUpdate()->first();
                                if ($lockedUser) {
                                    $lockedUser->addBalance($lockedOrder->charge);
                                    \App\Models\Transaction::add($lockedUser->id, $lockedOrder->charge, 'credit', 'refund', "Full refund for canceled order #{$lockedOrder->id}");
                                }

                                $lockedOrder->update([
                                    'status' => 'canceled',
                                    'is_refunded' => true,
                                    'error_log' => 'Canceled by provider'
                                ]);
                                $lockedOrder->logStatusChange('canceled', "Order canceled by provider. Full refund issued.", $statusData);
                                $refundedCount++;
                            } else {
                                $lockedOrder->update(['status' => 'canceled']);
                            }
                        }
                        elseif ($newStatus === 'partial') {
                            $remains = isset($statusData['remains']) ? (int) $statusData['remains'] : 0;
                            
                            if (!$lockedOrder->is_refunded && $remains > 0 && $lockedOrder->quantity > 0) {
                                $refundAmount = ($lockedOrder->charge / $lockedOrder->quantity) * $remains;
                                
                                $lockedUser = \App\Models\User::where('id', $user->id)->lockForUpdate()->first();
                                if ($lockedUser) {
                                    $lockedUser->addBalance($refundAmount);
                                    \App\Models\Transaction::add($lockedUser->id, $refundAmount, 'credit', 'refund', "Partial refund for order #{$lockedOrder->id} ({$remains} remains)");
                                }
                                
                                $lockedOrder->update([
                                    'status' => 'partial',
                                    'is_refunded' => true,
                                    'error_log' => "Partial delivery. Refunded {$refundAmount} for {$remains} undelivered items."
                                ]);
                                $lockedOrder->logStatusChange('partial', "Partial delivery detected ($remains remains). Partial refund issued.", $statusData);
                                $refundedCount++;
                            } else {
                                $lockedOrder->update(['status' => 'partial']);
                                $lockedOrder->logStatusChange('partial', "Order marked as partial by provider.", $statusData);
                            }
                        }
                        else {
                            // Just update the status (e.g. from pending to in_progress)
                            $lockedOrder->update(['status' => $newStatus]);
                            $lockedOrder->logStatusChange($newStatus, "Status updated to " . ucfirst($newStatus), $statusData);
                        }
                    });
                }
            }
        }

        $this->info("Order Status Check Complete! Completed: {$completedCount}, Refunded/Canceled: {$refundedCount}");
    }
}
