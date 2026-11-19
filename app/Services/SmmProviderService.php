<?php

namespace App\Services;

use App\Models\Provider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmmProviderService
{
    /**
     * Place an order with the specified provider.
     *
     * @param Provider $provider
     * @param string $providerServiceId
     * @param string $link
     * @param int $quantity
     * @return int|null Returns the Provider's Order ID on success, or null on failure.
     */
    public function placeOrder(Provider $provider, string $providerServiceId, string $link, int $quantity): ?int
    {
        try {
            // SMM v2 APIs typically expect a POST request with the following parameters
            $response = Http::timeout(10)->connectTimeout(5)->post($provider->api_url, [
                'key'      => $provider->api_key,
                'action'   => 'add',
                'service'  => $providerServiceId,
                'link'     => $link,
                'quantity' => $quantity,
            ]);

            $result = $response->json();

            // A provider server error can occur after accepting the order.
            // Do not fail over or refund on an ambiguous 5xx response.
            if ($response->serverError()) {
                Log::warning("SMM provider returned HTTP {$response->status()} with ambiguous purchase outcome ({$provider->name}).");
                throw new \App\Exceptions\ProviderTimeoutException("SMM provider purchase outcome could not be confirmed.");
            }

            // Successful orders usually return an array containing 'order' (the order ID)
            if ($response->successful() && isset($result['order'])) {
                return (int) $result['order'];
            }

            // If the provider returned an error message, log it
            if (isset($result['error'])) {
                Log::error("SMM API Error ({$provider->name}): " . $result['error']);
            } else {
                Log::error("SMM API Unknown Error ({$provider->name}): " . $response->body());
            }

            return null;

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            // Provider may have accepted the order before the connection timed out.
            Log::warning("SMM API purchase outcome is ambiguous ({$provider->name}): " . $e->getMessage());
            throw new \App\Exceptions\ProviderTimeoutException("SMM provider purchase outcome could not be confirmed.", 0, $e);
        } catch (\Exception $e) {
            Log::error("SMM API Connection Failed ({$provider->name}): " . $e->getMessage());
            return null;
        }
    }

    /**
     * Check the current reseller balance for a specific provider.
     *
     * @param Provider $provider
     * @return float|null
     */
    public function getBalance(Provider $provider): ?float
    {
        try {
            $response = Http::timeout(10)->connectTimeout(5)->post($provider->api_url, [
                'key'    => $provider->api_key,
                'action' => 'balance',
            ]);

            $result = $response->json();

            if ($response->successful() && isset($result['balance'])) {
                return (float) $result['balance'];
            }

            return null;

        } catch (\Exception $e) {
            Log::error("SMM API Balance Check Failed ({$provider->name}): " . $e->getMessage());
            return null;
        }
    }

    /**
     * Fetch all services offered by the provider.
     *
     * @param Provider $provider
     * @return array|null
     */
    public function getServices(Provider $provider): ?array
    {
        try {
            // SMM Panels can return MASSIVE amounts of data for the 'services' action (several MBs)
            // We increase the timeout and use a more resilient connection
            $response = Http::timeout(120) // Increased to 2 minutes
                ->connectTimeout(30)
                ->withHeaders(['Accept' => 'application/json'])
                ->post($provider->api_url, [
                    'key'    => $provider->api_key,
                    'action' => 'services',
                ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // Ensure we actually got an array of services
                if (is_array($data)) {
                    return $data;
                }
                
                Log::error("SMM API ({$provider->name}): Services response was not an array. Body snippet: " . substr($response->body(), 0, 500));
                return null;
            }

            Log::error("SMM API Services Fetch Failed ({$provider->name}) - Status: {$response->status()} - Body: " . substr($response->body(), 0, 500));
            return null;

        } catch (\Exception $e) {
            Log::error("SMM API Services Fetch Exception ({$provider->name}): " . $e->getMessage());
            return null;
        }
    }

    /**
     * Check the status of an existing order on the provider.
     *
     * @param Provider $provider
     * @param string $providerOrderId
     * @return array|null Returns array with 'status', 'remains', 'start_count', etc.
     */
    public function getOrderStatus(Provider $provider, string $providerOrderId): ?array
    {
        try {
            $response = Http::timeout(10)->connectTimeout(5)->post($provider->api_url, [
                'key'    => $provider->api_key,
                'action' => 'status',
                'order'  => $providerOrderId,
            ]);

            if ($response->successful()) {
                $result = $response->json();
                
                // Typical SMM API response contains 'status' or an 'error'
                if (isset($result['status'])) {
                    return $result;
                }
            }

            return null;

        } catch (\Exception $e) {
            Log::error("SMM API Order Status Fetch Failed ({$provider->name}): " . $e->getMessage());
            return null;
        }
    }

    /**
     * Request a refill for an existing order on the provider.
     *
     * @param Provider $provider
     * @param string $providerOrderId
     * @return array|null Returns the API response on success, or null on failure.
     */
    public function refillOrder(Provider $provider, string $providerOrderId): ?array
    {
        try {
            $response = Http::timeout(10)->connectTimeout(5)->post($provider->api_url, [
                'key'    => $provider->api_key,
                'action' => 'refill',
                'order'  => $providerOrderId,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error("SMM API Refill Failed ({$provider->name}): " . $response->body());
            return null;

        } catch (\Exception $e) {
            Log::error("SMM API Refill Exception ({$provider->name}): " . $e->getMessage());
            return null;
        }
    }
}
