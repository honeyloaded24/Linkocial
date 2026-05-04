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
        if (!$success && !$this->order->is_refunded) {
            $user = User::find($this->order->user_id);
            if ($user) {
                \Illuminate\Support\Facades\DB::table('users')->where('id', $user->id)->increment('balance', (float)$this->order->charge);
                
                \App\Models\Transaction::add(
                    $user->id, 
                    $this->order->charge, 
                    'credit', 
                    'refund', 
                    "Auto-refund for failed order #{$this->order->id}"
                );

                $this->order->update([
                    'status' => 'failed',
                    'is_refunded' => true
                ]);
            }
        }
    }
}
