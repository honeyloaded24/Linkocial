<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\VirtualNumberOrder;
use App\Models\Transaction;
use App\Services\SmsPoolService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckExpiredVirtualNumbers extends Command
{
    protected $signature = 'vn:check-expired';
    protected $description = 'Check and refund expired or completed virtual number orders that users abandoned';

    public function handle(SmsPoolService $smspool, \App\Services\GrizzlySmsService $grizzly)
    {
        // Crash Recovery: Check for orders stuck in 'processing' state for > 5 minutes (indicating a process crash before provider response)
        $staleProcessing = VirtualNumberOrder::where('status', 'processing')
            ->where('created_at', '<', now()->subMinutes(5))
            ->get();

        foreach ($staleProcessing as $stale) {
            DB::transaction(function () use ($stale) {
                $locked = VirtualNumberOrder::where('id', $stale->id)->lockForUpdate()->first();
                if ($locked && $locked->status === 'processing') {
                    $locked->update([
                        'status' => 'failed',
                        'sms_text' => 'Crash recovery: order timed out in processing state.',
                    ]);
                    $user = \App\Models\User::where('id', $locked->user_id)->lockForUpdate()->first();
                    if ($user) {
                        $user->addBalance($locked->cost);
                        Transaction::add(
                            $user->id,
                            $locked->cost,
                            'credit',
                            'refund',
                            "Automatic Refund: Unresolved virtual number checkout attempt"
                        );
                    }
                    $this->info("Crash recovery: Processed stale processing order {$locked->id} and refunded user.");
                }
            });
        }

        // Reconcile 'unresolved' orders: orders where a provider timeout occurred
        // We only attempt to auto-refund unresolved orders after 20 minutes (well beyond the SMSPool 1-minute activation window)
        // and only when SMSPool's active orders confirms no order exists
        $unresolvedOrders = VirtualNumberOrder::where('status', 'unresolved')
            ->where('created_at', '<', now()->subMinutes(20))
            ->get();

        if ($unresolvedOrders->isNotEmpty()) {
            $smspoolActive = [];
            try {
                $smspoolActive = $smspool->getActiveOrders();
            } catch (\Throwable $e) {
                Log::warning("Could not fetch active orders for unresolved reconciliation: " . $e->getMessage());
            }

            foreach ($unresolvedOrders as $unresolved) {
                DB::transaction(function () use ($unresolved, $smspoolActive) {
                    $locked = VirtualNumberOrder::where('id', $unresolved->id)->lockForUpdate()->first();
                    if (!$locked || $locked->status !== 'unresolved') {
                        return;
                    }

                    // Check if any active order on SMSPool matches
                    $hasMatchingActive = false;
                    if (is_array($smspoolActive)) {
                        foreach ($smspoolActive as $ao) {
                            $aoCountry = (string)($ao['country_id'] ?? $ao['country'] ?? '');
                            $aoService = (string)($ao['service_id'] ?? $ao['service'] ?? '');
                            if (($aoCountry === (string)$locked->country || empty($aoCountry)) &&
                                ($aoService === (string)$locked->product || empty($aoService))) {
                                $hasMatchingActive = true;
                                break;
                            }
                        }
                    }

                    if (!$hasMatchingActive) {
                        // Safe to refund: > 20 minutes elapsed, no matching order on SMSPool
                        $locked->update([
                            'status' => 'failed',
                            'sms_text' => 'Reconciled: no provider order found after 20 minutes. Refunded.',
                        ]);
                        $user = \App\Models\User::where('id', $locked->user_id)->lockForUpdate()->first();
                        if ($user) {
                            $user->addBalance($locked->cost);
                            Transaction::add(
                                $user->id,
                                $locked->cost,
                                'credit',
                                'refund',
                                "Automatic Refund: Virtual number order unconfirmed on provider"
                            );
                        }
                        $this->info("Unresolved order {$locked->id} refunded safely after 20m verification.");
                    } else {
                        Log::warning("Unresolved order {$locked->id} matches active provider orders. Preserving for manual admin review.");
                    }
                });
            }
        }

        // Find orders that are pending and past their expiration time
        // We add a 2-minute buffer to ensure provider has already processed the expiry
        $orders = VirtualNumberOrder::where('status', 'pending')
            ->where('expires_at', '<', now()->subMinutes(2))
            ->get();

        foreach ($orders as $order) {
            try {
                // If the number already received an SMS code previously (e.g. user requested resend and abandoned), do NOT refund
                if (!empty($order->sms_code) || !empty($order->sms_text)) {
                    $order->update(['status' => 'received']);
                    $this->info("Order {$order->id} already received SMS previously. Marked received without refund.");
                    continue;
                }

                $provider = ($order->server === 'server2') ? $grizzly : $smspool;
                $checkData = $provider->checkOrder($order->api_order_id);
                $status = isset($checkData['status']) ? (int)$checkData['status'] : 1;

                if ($status === 3) {
                    // SMS was received but user never saw it
                    $order->update([
                        'status' => 'received',
                        'sms_text' => $checkData['full_sms'] ?? $checkData['sms'] ?? '',
                        'sms_code' => $checkData['sms'] ?? ''
                    ]);
                    $this->info("Order {$order->id} was received. Updated.");
                } elseif ($status === 2 || $status === 5 || $status === 6) {
                    // Provider marked expired (2), canceled (5), or refunded (6)
                    $this->refundOrder($order);
                    $this->info("Order {$order->id} was refunded/expired by provider (status {$status}). Processed local refund.");
                } else {
                    // Still pending on provider but time is up locally: attempt cancel on provider and refund locally
                    try {
                        $provider->cancelOrder($order->api_order_id);
                    } catch (\Throwable $cancelEx) {
                        Log::warning("Could not cancel order {$order->api_order_id} on provider: " . $cancelEx->getMessage());
                    }
                    $this->refundOrder($order);
                    $this->info("Order {$order->id} was locally expired and refunded.");
                }
            } catch (\Exception $e) {
                Log::error('Background VN check failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
            }
        }
    }

    protected function refundOrder(VirtualNumberOrder $order)
    {
        DB::transaction(function () use ($order) {
            $lockedOrder = VirtualNumberOrder::where('id', $order->id)
                ->lockForUpdate()
                ->first();

            if (!$lockedOrder || $lockedOrder->status !== 'pending') {
                return;
            }

            $lockedOrder->update(['status' => 'canceled']);

            $lockedUser = \App\Models\User::where('id', $lockedOrder->user_id)
                ->lockForUpdate()
                ->first();

            if ($lockedUser) {
                $lockedUser->addBalance($lockedOrder->cost);

                if ($lockedOrder->cost > 0) {
                    Transaction::add(
                        $lockedUser->id, 
                        $lockedOrder->cost, 
                        'credit', 
                        'refund', 
                        "Refund for Virtual Number Purchase ({$lockedOrder->product} - {$lockedOrder->country})"
                    );
                }
            }
        });
    }
}
