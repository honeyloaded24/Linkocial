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
                    // Provider purchase may have succeeded before the process crashed.
                    // Keep this order unresolved until provider reconciliation confirms its outcome.
                    $locked->update([
                        'status' => 'unresolved',
                        'sms_text' => 'Crash recovery: provider purchase outcome unknown. Retained for reconciliation; no automatic refund issued.',
                    ]);
                    Log::warning("Stale processing virtual number order {$locked->id} moved to unresolved without refund.");
                }
            });
        }

        // ─────────────────────────────────────────────────────────────────────
        // Reconcile 'unresolved' orders: orders where a provider timeout occurred.
        // We only attempt to auto-refund unresolved orders after 20 minutes (well
        // beyond the SMSPool 1-minute activation window).
        //
        // Multi-stage verification to protect business finances:
        // 1. Inspect /request/active
        // 2. Inspect /request/history
        // 3. Inspect /sms/check if a candidate provider order ID is known
        // 4. Distinguish active, expired, provider-refunded, and genuinely absent orders
        // 5. If provider API calls fail or time out, keep unresolved (NEVER refund on error)
        // 6. Only refund if BOTH active and history confirm zero matching order was created
        // 7. If matching order exists on provider:
        //    - If provider refunded or expired with no code -> safe to refund customer
        //    - If completed / received -> adopt and fulfill without refund
        //    - If still ambiguous or active -> preserve for manual admin review
        // ─────────────────────────────────────────────────────────────────────
        // SMSPool reconciliation must never inspect or refund Grizzly orders.
        // Legacy rows with a null server are treated as Server 1 for compatibility.
        $unresolvedOrders = VirtualNumberOrder::where('status', 'unresolved')
            ->where(function ($query) {
                $query->where('server', 'server1')->orWhereNull('server');
            })
            ->where('created_at', '<', now()->subMinutes(20))
            ->get();

        if ($unresolvedOrders->isNotEmpty()) {
            $activeOrdersFetched = false;
            $historyOrdersFetched = false;
            $smspoolActive = [];
            $smspoolHistory = [];

            try {
                $smspoolActive = $smspool->getActiveOrders();
                $activeOrdersFetched = is_array($smspoolActive);
            } catch (\Throwable $e) {
                Log::warning("Could not fetch active orders for unresolved reconciliation: " . $e->getMessage());
            }

            try {
                $smspoolHistory = $smspool->getOrderHistory();
                $historyOrdersFetched = is_array($smspoolHistory);
            } catch (\Throwable $e) {
                Log::warning("Could not fetch history orders for unresolved reconciliation: " . $e->getMessage());
            }

            foreach ($unresolvedOrders as $unresolved) {
                DB::transaction(function () use (
                    $unresolved,
                    $smspoolActive,
                    $smspoolHistory,
                    $activeOrdersFetched,
                    $historyOrdersFetched,
                    $smspool
                ) {
                    $locked = VirtualNumberOrder::where('id', $unresolved->id)->lockForUpdate()->first();
                    if (!$locked || $locked->status !== 'unresolved') {
                        return;
                    }

                    // FINANCIAL SAFETY RULE 1:
                    // If either active or history endpoint failed (e.g. API timeout, connectivity loss),
                    // we CANNOT guarantee absence of order. Preserve unresolved!
                    if (!$activeOrdersFetched || !$historyOrdersFetched) {
                        Log::warning("Reconciliation API timeout or failure occurred while checking unresolved order {$locked->id}. Retaining unresolved status.");
                        return;
                    }

                    // Helper to match provider order item with local order
                    $matchesLocal = function ($item) use ($locked) {
                        $c = (string)($item['country_id'] ?? $item['country'] ?? $item['short_name'] ?? '');
                        $s = (string)($item['service_id'] ?? $item['service'] ?? '');
                        $countryMatch = empty($c) || $c === (string)$locked->country;
                        $serviceMatch = empty($s) || $s === (string)$locked->product || strcasecmp($s, (string)$locked->product) === 0;
                        return $countryMatch && $serviceMatch;
                    };

                    // Check active orders for matching order
                    $matchingActive = null;
                    foreach ($smspoolActive as $ao) {
                        $aoId = (string)($ao['order_id'] ?? $ao['orderid'] ?? $ao['order_code'] ?? '');
                        if (!empty($aoId) && !VirtualNumberOrder::where('api_order_id', $aoId)->where('id', '!=', $locked->id)->exists()) {
                            if ($matchesLocal($ao)) {
                                $matchingActive = $ao;
                                break;
                            }
                        }
                    }

                    // Check history orders for matching order
                    $matchingHistory = null;
                    foreach ($smspoolHistory as $ho) {
                        $hoId = (string)($ho['order_code'] ?? $ho['order_id'] ?? $ho['orderid'] ?? '');
                        if (!empty($hoId) && !VirtualNumberOrder::where('api_order_id', $hoId)->where('id', '!=', $locked->id)->exists()) {
                            if ($matchesLocal($ho)) {
                                $matchingHistory = $ho;
                                break;
                            }
                        }
                    }

                    // Scenario A: Order exists in active orders on SMSPool
                    if ($matchingActive !== null) {
                        $matchedId = (string)($matchingActive['order_id'] ?? $matchingActive['orderid'] ?? $matchingActive['order_code'] ?? '');
                        Log::alert("Unresolved order {$locked->id} matches active provider order {$matchedId}. Preserving for administrative review to prevent cross-assignment.");
                        $locked->update([
                            'sms_text' => "Active order {$matchedId} detected on provider. Preserved for admin review.",
                        ]);
                        return;
                    }

                    // Scenario B: Order exists in history on SMSPool
                    if ($matchingHistory !== null) {
                        $histId = (string)($matchingHistory['order_code'] ?? $matchingHistory['order_id'] ?? $matchingHistory['orderid'] ?? '');
                        $histStatus = (int)($matchingHistory['status'] ?? 0);

                        // If individual status can be queried via /sms/check, do so for confirmation
                        if (!empty($histId)) {
                            try {
                                $checkData = $smspool->checkOrder($histId);
                                if (isset($checkData['status'])) {
                                    $histStatus = (int)$checkData['status'];
                                }
                            } catch (\Throwable $checkEx) {
                                Log::warning("Could not check individual status for provider order {$histId}: " . $checkEx->getMessage());
                            }
                        }

                        // Status values in SMSPool:
                        // 1 = pending, 2 = expired, 3 = completed (SMS received), 5/6 = refunded/canceled
                        if ($histStatus === 3) {
                            // Completed / SMS received: fulfill order, do NOT refund!
                            $locked->update([
                                'api_order_id' => $histId,
                                'phone_number' => $matchingHistory['phonenumber'] ?? $locked->phone_number,
                                'status' => 'received',
                                'sms_code' => $matchingHistory['code'] ?? '',
                                'sms_text' => $matchingHistory['full_code'] ?? 'SMS verified from history.',
                            ]);
                            $this->info("Unresolved order {$locked->id} fulfilled from provider history ({$histId}).");
                            return;
                        } elseif ($histStatus === 2 || $histStatus === 5 || $histStatus === 6) {
                            // Provider expired or refunded: safe to refund user idempotently
                            $this->executeIdempotentRefund(
                                $locked,
                                "Automatic Refund: Provider marked order {$histId} as " . ($histStatus === 2 ? 'expired' : 'refunded')
                            );
                            $this->info("Unresolved order {$locked->id} refunded because provider history status is {$histStatus}.");
                            return;
                        } else {
                            // Still pending or ambiguous: preserve for admin review
                            Log::warning("Unresolved order {$locked->id} has ambiguous history order {$histId} (status {$histStatus}). Preserving.");
                            return;
                        }
                    }

                    // Scenario C: Genuinely absent on both active and history endpoints after > 20 minutes
                    $this->executeIdempotentRefund(
                        $locked,
                        "Automatic Refund: Virtual number order unconfirmed on provider (absent from active and history)"
                    );
                    $this->info("Unresolved order {$locked->id} refunded safely: verified absent on active and history endpoints.");
                });
            }
        }

        // Find orders that are pending and past their expiration time
        // We add a 2-minute buffer to ensure provider has already processed the expiry
        $orders = VirtualNumberOrder::where('status', 'pending')
            ->where('expires_at', '<', now()->subMinutes(2))
            ->get();

        foreach ($orders as $order) {
            // Serialize scheduled reconciliation with user status/cancel/resend actions.
            $actionLock = Cache::lock("vn_order_action_{$order->id}", 60);
            if (!$actionLock->get()) {
                continue;
            }

            try {
                $order = $order->fresh();
                if (!$order || $order->status !== 'pending') {
                    continue;
                }

                // If the number already received an SMS code previously (e.g. user requested resend and abandoned), do NOT refund
                if (!empty($order->sms_code) || !empty($order->sms_text)) {
                    DB::transaction(function () use ($order) {
                        $locked = VirtualNumberOrder::where('id', $order->id)->lockForUpdate()->first();
                        if ($locked && $locked->status === 'pending' && (!empty($locked->sms_code) || !empty($locked->sms_text))) {
                            $locked->update(['status' => 'received']);
                        }
                    });
                    $this->info("Order {$order->id} already received SMS previously. Marked received without refund.");
                    continue;
                }

                $provider = ($order->server === 'server2') ? $grizzly : $smspool;
                $checkData = $provider->checkOrder($order->api_order_id);
                $status = isset($checkData['status']) ? (int)$checkData['status'] : 1;

                if ($status === 3) {
                    // SMS was received but user never saw it
                    DB::transaction(function () use ($order, $checkData) {
                        $locked = VirtualNumberOrder::where('id', $order->id)->lockForUpdate()->first();
                        if ($locked && $locked->status === 'pending') {
                            $locked->update([
                                'status' => 'received',
                                'sms_text' => $checkData['full_sms'] ?? $checkData['sms'] ?? '',
                                'sms_code' => $checkData['sms'] ?? ''
                            ]);
                        }
                    });
                    $this->info("Order {$order->id} was received. Updated.");
                } elseif ($status === 2 || $status === 5 || $status === 6) {
                    // Provider marked expired (2), canceled (5), or refunded (6)
                    $this->refundOrder($order);
                    $this->info("Order {$order->id} was refunded/expired by provider (status {$status}). Processed local refund.");
                } else {
                    // Still pending on provider: refund only after cancellation is confirmed.
                    try {
                        $provider->cancelOrder($order->api_order_id);
                        $this->refundOrder($order);
                        $this->info("Order {$order->id} cancellation confirmed and locally refunded.");
                    } catch (\\Throwable $cancelEx) {
                        Log::warning("Cancellation was not confirmed for order {$order->api_order_id}; refund withheld: " . $cancelEx->getMessage());
                        $order->update([
                            'sms_text' => 'Cancellation not confirmed by provider. Refund withheld pending reconciliation.',
                        ]);
                    }
                }
            } catch (\\Throwable $e) {
                Log::error('Background VN check failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
            } finally {
                $actionLock->release();
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

    /**
     * Idempotently refund an unresolved order.
     * Ensures an order is never refunded twice and only if status is strictly 'unresolved'.
     */
    protected function executeIdempotentRefund(VirtualNumberOrder $order, string $reason): void
    {
        $lockedOrder = VirtualNumberOrder::where('id', $order->id)
            ->lockForUpdate()
            ->first();

        if (!$lockedOrder || $lockedOrder->status !== 'unresolved') {
            return;
        }

        $lockedOrder->update([
            'status' => 'failed',
            'sms_text' => $reason,
        ]);

        $lockedUser = \App\Models\User::where('id', $lockedOrder->user_id)
            ->lockForUpdate()
            ->first();

        if ($lockedUser && $lockedOrder->cost > 0) {
            $lockedUser->addBalance($lockedOrder->cost);
            Transaction::add(
                $lockedUser->id,
                $lockedOrder->cost,
                'credit',
                'refund',
                $reason
            );
        }
    }
}
