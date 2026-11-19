<?php

namespace App\Services;

use App\Models\Service;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

class ProviderRouter
{
    protected SmmProviderService $smmService;

    public function __construct(SmmProviderService $smmService)
    {
        $this->smmService = $smmService;
    }

    /**
     * Route the order to the appropriate provider with failover support.
     *
     * @param Order $order
     * @param Service $service
     * @return bool True if successfully placed, false if all providers failed.
     */
    public function routeOrder(Order $order, Service $service): bool
    {
        // Get all active mappings for this service, ordered by priority (1 is highest)
        // Assuming $service->mappings() relationship exists in the Service model
        $mappings = $service->mappings()->where('is_active', true)->orderBy('priority', 'asc')->get();

        if ($mappings->isEmpty()) {
            Log::error("No active provider mappings found for Service ID: {$service->id}");
            $order->update(['status' => 'failed', 'error_log' => 'No active provider mappings.']);
            return false;
        }

        foreach ($mappings as $mapping) {
            $provider = $mapping->provider;

            if (!$provider->is_active) {
                continue;
            }

            // Optional: Real-time Balance Check
            // Depending on performance needs, you might want to cache the balance or only check it periodically.
            // For now, doing it in real-time as requested:
            $balance = $this->smmService->getBalance($provider);
            
            // Calculate total cost (assuming original_price is per 1000 items)
            $totalCost = ($mapping->original_price / 1000) * $order->quantity;
            
            if ($balance !== null && $balance < $totalCost) {
                Log::warning("Insufficient balance on Provider {$provider->name}. Required: {$totalCost}, Available: {$balance}");
                continue; // Failover: Try next provider
            }

            // Attempt to place the order
            try {
                $providerOrderId = $this->smmService->placeOrder(
                    $provider,
                    $mapping->provider_service_id,
                    $order->link,
                    $order->quantity
                );
            } catch (\\App\\Exceptions\\ProviderTimeoutException $timeout) {
                // Do not fail over: the provider may have created the order before the response was lost.
                $order->update([
                    'status' => 'unresolved',
                    'provider_id' => $provider->id,
                    'error_log' => trim(($order->error_log ?? '') . ' | Provider purchase outcome ambiguous; automatic refund/failover withheld.'),
                ]);
                Log::critical("SMM order {$order->id} has an ambiguous provider purchase outcome; preserving charge.");
                return true;
            }

            if ($providerOrderId !== null) {
                // Success! Update order and break out of failover loop
                $order->update([
                    'provider_id' => $provider->id,
                    'provider_order_id' => $providerOrderId,
                    'provider_cost' => $totalCost,
                    'status' => 'processing', // Instant progress for high-authority visual feedback
                    'error_log' => null // Clear any previous failover warnings
                ]);
                
                return true;
            } else {
                Log::warning("Provider {$provider->name} failed to process order. Attempting failover if available.");
                // Append to error log but don't mark as failed yet
                $order->update(['error_log' => trim($order->error_log . " | Failed on {$provider->name}.")]);
            }
        }

        // If we exit the loop, all attempts failed
        $order->update(['status' => 'failed', 'error_log' => trim($order->error_log . " | All provider attempts failed.")]);
        return false;
    }
}
