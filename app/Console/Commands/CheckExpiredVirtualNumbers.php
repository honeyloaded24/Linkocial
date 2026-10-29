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
