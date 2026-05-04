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
                        
                        $user = $order->user;
                        $oldStatus = $order->status;

                        if ($newStatus === 'completed') {
                            $order->update(['status' => 'completed']);
                            $order->logStatusChange('completed', "Order marked as completed by provider.", $statusData);
                            $completedCount++;
                        } 
                        elseif ($newStatus === 'canceled') {
                            // Full Refund
                            $order->update(['status' => 'canceled', 'error_log' => 'Canceled by provider']);
                            DB::table('users')->where('id', $user->id)->increment('balance', (float)$order->charge);
                            $user->refresh();
                            
                            \App\Models\Transaction::add($user->id, $order->charge, 'credit', 'refund', "Full refund for canceled order #{$order->id}");
                            $order->logStatusChange('canceled', "Order canceled by provider. Full refund issued.", $statusData);
                            
                            $refundedCount++;
                        }
                        elseif ($newStatus === 'partial') {
                            // Partial Refund calculation based on 'remains' returned by the provider
                            $remains = isset($statusData['remains']) ? (int) $statusData['remains'] : 0;
                            
                            if ($remains > 0 && $order->quantity > 0) {
                                $refundAmount = ($order->charge / $order->quantity) * $remains;
                                
                                DB::table('users')->where('id', $user->id)->increment('balance', (float)$refundAmount);
                                $user->refresh(); // Update the local user model
                                
                                \App\Models\Transaction::add($user->id, $refundAmount, 'credit', 'refund', "Partial refund for order #{$order->id} ({$remains} remains)");
                                
                                $order->update([
                                    'status' => 'partial',
                                    'error_log' => "Partial delivery. Refunded {$refundAmount} NGN for {$remains} undelivered items."
                                ]);
                                $order->logStatusChange('partial', "Partial delivery detected ($remains remains). Partial refund issued.", $statusData);
                                $refundedCount++;
                            } else {
                                $order->update(['status' => 'partial']);
                                $order->logStatusChange('partial', "Order marked as partial by provider.", $statusData);
                            }
                        }
                        else {
                            // Just update the status (e.g. from pending to in_progress)
                            $order->update(['status' => $newStatus]);
                            $order->logStatusChange($newStatus, "Status updated to " . ucfirst($newStatus), $statusData);
                        }
                    });
                }
            }
        }

        $this->info("Order Status Check Complete! Completed: {$completedCount}, Refunded/Canceled: {$refundedCount}");
    }
}
