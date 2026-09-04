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
        $this->baseUrl = Setting::get('fadded_api_url', config('services.fadded.url', 'https://fadded.net/api'));
        $this->apiKey = Setting::get('fadded_api_key', config('services.fadded.key'));
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
            $url = rtrim($this->baseUrl, '/');
            if (!str_ends_with($url, '/products')) {
                $url .= '/products';
            }

            $response = Http::timeout(20)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'X-Api-Key' => $this->apiKey,
                    'Accept' => 'application/json',
                ])
                ->get($url);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'data' => $data['data'] ?? $data ?? []
                ];
            }

            $msg = $response->json('message') ?? 'Unknown error';
            return [
                'success' => false,
                'message' => "Fadded API ({$url}) returned status {$response->status()}: {$msg}"
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
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json'
                ])
                ->post($this->baseUrl . '/order', [
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
