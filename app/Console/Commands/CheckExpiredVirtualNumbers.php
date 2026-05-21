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

    public function handle(SmsPoolService $smspool)
    {
        // Find orders that are pending and past their expiration time
        // We add a 2-minute buffer to ensure SMSPool has already processed the expiry
        $orders = VirtualNumberOrder::where('status', 'pending')
            ->where('expires_at', '<', now()->subMinutes(2))
            ->get();

        foreach ($orders as $order) {
            try {
                $checkData = $smspool->checkOrder($order->api_order_id);
                $status = isset($checkData['status']) ? (int)$checkData['status'] : 1;

                if ($status === 3) {
                    // SMS was received but user never saw it
                    $order->update([
                        'status' => 'received',
                        'sms_text' => $checkData['full_sms'] ?? $checkData['sms'] ?? '',
                        'sms_code' => $checkData['sms'] ?? ''
                    ]);
                    $this->info("Order {$order->id} was received. Updated.");
                } elseif ($status === 5 || $status === 6) {
                    // SMSPool refunded it
                    $this->refundOrder($order);
                    $this->info("Order {$order->id} was refunded by SMSPool. Processed local refund.");
                } else {
                    // Still pending on SMSPool but time is up locally? Cancel it forcefully.
                    $smspool->cancelOrder($order->api_order_id);
                    $this->refundOrder($order);
                    $this->info("Order {$order->id} was forcefully cancelled and refunded.");
                }
            } catch (\Exception $e) {
                Log::error('Background VN check failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
            }
        }
    }

    protected function refundOrder(VirtualNumberOrder $order)
    {
        if ($order->status !== 'pending') return;

        DB::transaction(function () use ($order) {
            $order->update(['status' => 'canceled']);
            $order->user->increment('balance', $order->cost);
            
            if ($order->cost > 0) {
                Transaction::add(
                    $order->user_id, 
                    $order->cost, 
                    'credit', 
                    'refund', 
                    "Refund for abandoned Virtual Number ({$order->product})"
                );
            }

            // Rollback coupon usage if applicable
            if ($order->coupon_code) {
                $coupon = \App\Models\Coupon::where('code', $order->coupon_code)->first();
                if ($coupon) {
                    $usage = \App\Models\CouponUsage::where('coupon_id', $coupon->id)
                        ->where('virtual_number_order_id', $order->id)
                        ->first();
                    if ($usage) {
                        $usage->delete();
                        $coupon->decrement('usage_count');
                    }
                }
            }
        });
    }
}
