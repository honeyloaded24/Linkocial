<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use App\Services\ProviderRouter;

class ProcessSmmOrder implements ShouldQueue
{
    use Queueable;

    public $order;
    public $service;

    /**
     * Create a new job instance.
     */
    public function __construct(Order $order, Service $service)
    {
        $this->order = $order;
        $this->service = $service;
    }

    /**
     * Execute the job.
     */
    public function handle(ProviderRouter $router): void
    {
        // Serialize submissions for each order so concurrent queue retries cannot
        // send duplicate provider requests while the order is still pending.
        $lock = Cache::lock("smm_order_submission_{$this->order->id}", 120);
        if (!$lock->get()) {
            return;
        }

        try {
            $currentOrder = Order::find($this->order->id);
            if (!$currentOrder || $currentOrder->status !== 'pending' || $currentOrder->is_refunded) {
                return;
            }

            $success = $router->routeOrder($currentOrder, $this->service);

            // Refund only confirmed failures. Ambiguous provider timeouts are
            // marked unresolved by ProviderRouter and return success-like handling.
            if (!$success) {
                \\Illuminate\\Support\\Facades\\DB::transaction(function () use ($currentOrder) {
                    $lockedOrder = Order::where('id', $currentOrder->id)
                        ->lockForUpdate()
                        ->first();

                    if (!$lockedOrder || $lockedOrder->is_refunded || $lockedOrder->status !== 'failed') {
                        return;
                    }

                    $lockedUser = User::where('id', $lockedOrder->user_id)
                        ->lockForUpdate()
                        ->first();

                    if ($lockedUser) {
                        $refundCharge = (float) $lockedOrder->charge;
                        $lockedUser->addBalance($refundCharge);

                        \\App\\Models\\Transaction::add(
                            $lockedUser->id,
                            $refundCharge,
                            'credit',
                            'refund',
                            "Auto-refund for failed order #{$lockedOrder->id}"
                        );

                        $lockedOrder->update(['is_refunded' => true]);
                    }
                });
            }
        } finally {
            $lock->release();
        }
    }
}
