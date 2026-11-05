<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Setting;

class FaddedApi
{
    public const DEFAULT_BASE_URL = 'https://api.fadded.net/api/v1/reseller';

    protected $baseUrl;
    protected $apiKey;

    public function __construct()
    {
        $dbUrl = Setting::get('fadded_api_url');
        if (!empty($dbUrl)) {
            $normalizedDb = self::normalizeApiUrl($dbUrl);
            // If the database has an old fadded.net URL or incomplete path, migrate it in DB
            if ($normalizedDb !== $dbUrl && str_contains($dbUrl, 'fadded.net')) {
                try {
                    Setting::set('fadded_api_url', $normalizedDb);
                } catch (\Throwable $e) {
                    // Safe fallback if DB is temporarily locked or offline
                }
            }
        }

        $url = Setting::get('fadded_api_url') ?: config('services.fadded.url', self::DEFAULT_BASE_URL);
        $this->baseUrl = self::normalizeApiUrl($url);

        $rawKey = Setting::get('fadded_api_key') ?: config('services.fadded.key');
        $this->apiKey = $rawKey !== null ? trim((string)$rawKey) : null;
    }

    /**
     * Normalize Fadded API URL to ensure the api.fadded.net host and /api/v1/reseller path are used.
     */
    public static function normalizeApiUrl(?string $url): string
    {
        if (empty($url) || trim($url) === '') {
            return self::DEFAULT_BASE_URL;
        }

        $url = trim($url);

        // Replace any legacy fadded.net hostname with api.fadded.net
        $url = preg_replace('#^https?://(www\.)?fadded\.net#i', 'https://api.fadded.net', $url);

        // Strip trailing slashes
        $url = rtrim($url, '/');

        // If URL is just host or missing /api/v1/reseller, normalize to full base path
        if ($url === 'https://api.fadded.net' || $url === 'https://api.fadded.net/api' || $url === 'https://api.fadded.net/api/v1') {
            return self::DEFAULT_BASE_URL;
        }

        // Strip endpoint suffixes if user entered a full endpoint URL
        $url = preg_replace('#/(products|order|balance)$#i', '', $url);

        return rtrim($url, '/');
    }

    /**
     * Get the full endpoint URL for a given action.
     */
    public function getEndpointUrl(string $endpoint): string
    {
        $base = self::normalizeApiUrl($this->baseUrl);
        return rtrim($base, '/') . '/' . ltrim($endpoint, '/');
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
            $url = $this->getEndpointUrl('products');

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
                'status' => $response->status(),
                'code' => $response->json('code'),
                'message' => "Fadded API ({$url}) returned status {$response->status()}: {$msg}"
            ];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Fadded getProducts timeout/connection error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Unable to connect to Fadded (Timeout). The provider’s server (api.fadded.net) took too long to respond or is temporarily unreachable. Please try again in a few moments.'
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
            $url = $this->getEndpointUrl('balance');

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
                'status' => $response->status(),
                'code' => $response->json('code'),
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
            $url = $this->getEndpointUrl('order');

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
                'status' => $response->status(),
                'code' => $response->json('code'),
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
