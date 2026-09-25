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
        $success = $router->routeOrder($this->order, $this->service);

        // Refund the user if all failovers failed AND not already refunded
        if (!$success) {
            \Illuminate\Support\Facades\DB::transaction(function () {
                $lockedOrder = Order::where('id', $this->order->id)
                    ->lockForUpdate()
                    ->first();

                if (!$lockedOrder || $lockedOrder->is_refunded) {
                    return;
                }

                $lockedUser = User::where('id', $lockedOrder->user_id)
                    ->lockForUpdate()
                    ->first();

                if ($lockedUser) {
                    $refundCharge = (float) $lockedOrder->charge;
                    $lockedUser->addBalance($refundCharge);

                    \App\Models\Transaction::add(
                        $lockedUser->id,
                        $refundCharge,
                        'credit',
                        'refund',
                        "Auto-refund for failed order #{$lockedOrder->id}"
                    );

                    $lockedOrder->update([
                        'status' => 'failed',
                        'is_refunded' => true
                    ]);
                }
            });
        }
    }
}
