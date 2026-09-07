<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Setting;

class FaddedApi
{
    protected $baseUrl;
    protected $apiKey;

    public function __construct()
    {
        $url = Setting::get('fadded_api_url') ?: config('services.fadded.url', 'https://fadded.net/api/v1/reseller');
        
        // Auto-correct legacy/wrong URLs
        if (empty($url) || $url === 'https://fadded.net/api' || $url === 'https://fadded.net/api/') {
            $url = 'https://fadded.net/api/v1/reseller';
        }

        $this->baseUrl = rtrim($url, '/');
        $this->apiKey = Setting::get('fadded_api_key') ?: config('services.fadded.key');
    }

    /**
     * Check whether API credentials are set
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Fetch products list from Fadded
     */
    public function getProducts(): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'Fadded API key is not configured. Please set your API key in Settings or .env.'
            ];
        }

        try {
            $url = $this->baseUrl;
            if (!str_ends_with($url, '/products')) {
                $url .= '/products';
            }

            $response = Http::timeout(45)
                ->retry(2, 500)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'X-Api-Key' => $this->apiKey,
                    'Accept' => 'application/json',
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
                ])
                ->get($url);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'data' => $data['data'] ?? $data['products'] ?? $data ?? []
                ];
            }

            $msg = $response->json('message') ?? 'Unknown error';
            return [
                'success' => false,
                'message' => "Fadded API ({$url}) returned status {$response->status()}: {$msg}"
            ];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Fadded getProducts timeout/connection error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Unable to connect to Fadded (Timeout). The provider’s server (fadded.net) took too long to respond or is temporarily unreachable. Please try again in a few moments.'
            ];
        } catch (\Exception $e) {
            Log::error('Fadded getProducts error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Unable to connect to Fadded: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Fetch Reseller account balance from Fadded
     */
    public function getBalance(): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'Fadded API key is not configured.'
            ];
        }

        try {
            $url = $this->baseUrl;
            if (str_ends_with($url, '/products') || str_ends_with($url, '/order')) {
                $url = dirname($url);
            }
            if (!str_ends_with($url, '/balance')) {
                $url .= '/balance';
            }

            $response = Http::timeout(20)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'X-Api-Key' => $this->apiKey,
                    'Accept' => 'application/json',
                ])
                ->get($url);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'message' => $response->json('message') ?? ('Status ' . $response->status())
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Place order for a digital log item
     */
    public function order(string $productKey, int $quantity, string $orderId): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'Fadded API key is not configured in Settings.'
            ];
        }

        try {
            $url = $this->baseUrl;
            if (str_ends_with($url, '/products') || str_ends_with($url, '/balance')) {
                $url = dirname($url);
            }
            if (!str_ends_with($url, '/order')) {
                $url .= '/order';
            }

            $response = Http::timeout(35)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'X-Api-Key' => $this->apiKey,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json'
                ])
                ->post($url, [
                    'product_key' => $productKey,
                    'quantity' => $quantity,
                    'custom_id' => $orderId
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            return [
                'success' => false,
                'message' => $response->json('message') ?? ('Provider error ' . $response->status())
            ];
        } catch (\Exception $e) {
            Log::error('Fadded order error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Connection to Fadded failed: ' . $e->getMessage()
            ];
        }
    }
}
