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
        $this->apiKey = self::normalizeApiKey($rawKey);
    }

    /**
     * Normalize Fadded API key: trim whitespace and remove duplicate Bearer prefix.
     */
    public static function normalizeApiKey(?string $key): ?string
    {
        if ($key === null) {
            return null;
        }

        $trimmed = trim((string)$key);
        if ($trimmed === '') {
            return null;
        }

        // Strip leading "Bearer " prefix if user entered it (case-insensitive)
        $clean = preg_replace('/^Bearer\s+/i', '', $trimmed);

        return trim($clean);
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
     * Build standard authentication headers.
     */
    protected function getAuthHeaders(bool $isPost = false): array
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Accept' => 'application/json',
        ];

        if ($isPost) {
            $headers['Content-Type'] = 'application/json';
        }

        return $headers;
    }

    /**
     * Check whether API credentials are set
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Map official Fadded error response to admin diagnostic and customer-safe messages.
     */
    public static function parseProviderError(int $status, ?string $code, ?string $rawMessage): array
    {
        $normalizedCode = strtoupper(trim((string)$code));

        $adminDiagnostic = match ($normalizedCode) {
            'API_KEY_REQUIRED' => 'Fadded API key is missing or not configured. (401 API_KEY_REQUIRED)',
            'INVALID_API_KEY' => 'Invalid or inactive Fadded API key. Please verify or re-issue your API key in your Fadded dashboard. (401 INVALID_API_KEY)',
            'SUBSCRIPTION_EXPIRED' => 'Fadded Reseller subscription has expired. Please renew your reseller subscription on fadded.net. (403 SUBSCRIPTION_EXPIRED)',
            'INSUFFICIENT_BALANCE' => 'Fadded Reseller wallet balance is insufficient to complete this order. Please top up your reseller account on fadded.net. (400 INSUFFICIENT_BALANCE)',
            'INSUFFICIENT_STOCK' => 'Fadded provider has insufficient stock available for this product. (400 INSUFFICIENT_STOCK)',
            'PRODUCT_NOT_FOUND' => 'Product was not found or is discontinued on Fadded. (404 PRODUCT_NOT_FOUND)',
            'VALIDATION_ERROR' => 'Fadded request validation error: ' . ($rawMessage ?: 'Invalid parameters.') . ' (422 VALIDATION_ERROR)',
            default => match ($status) {
                401 => 'Fadded authentication failed (401 Unauthorized). ' . ($rawMessage ?: 'Check API key.'),
                403 => 'Fadded access forbidden (403 Forbidden). Reseller subscription may be inactive or expired.',
                400 => 'Fadded bad request (400): ' . ($rawMessage ?: 'Check request payload or balance.'),
                404 => 'Fadded resource not found (404 Not Found): ' . ($rawMessage ?: 'Endpoint or product unavailable.'),
                422 => 'Fadded validation error (422 Unprocessable Entity): ' . ($rawMessage ?: 'Invalid request.'),
                default => 'Fadded API error (' . $status . '): ' . ($rawMessage ?: 'Unknown response from provider.')
            }
        };

        $customerMessage = match ($normalizedCode) {
            'INSUFFICIENT_STOCK' => 'This product is currently out of stock. Your wallet balance has been refunded.',
            'PRODUCT_NOT_FOUND' => 'This product is no longer available. Your wallet balance has been refunded.',
            default => 'Unable to complete order with provider at this time. Your wallet balance has been fully refunded. Please try again later or contact support.'
        };

        return [
            'code' => $normalizedCode ?: ('HTTP_' . $status),
            'admin_message' => $adminDiagnostic,
            'customer_message' => $customerMessage,
        ];
    }

    /**
     * Fetch products list from Fadded
     */
    public function getProducts(): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'code' => 'API_KEY_REQUIRED',
                'admin_message' => 'Fadded API key is not configured. Please set your API key in Settings or .env.',
                'message' => 'Fadded API key is not configured. Please set your API key in Settings or .env.'
            ];
        }

        try {
            $url = $this->getEndpointUrl('products');

            $response = Http::timeout(45)
                ->retry(2, 500)
                ->withHeaders($this->getAuthHeaders(false))
                ->get($url);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'data' => $data['data'] ?? $data['products'] ?? $data ?? []
                ];
            }

            $status = $response->status();
            $code = $response->json('code');
            $rawMsg = $response->json('message');
            $parsed = self::parseProviderError($status, $code, $rawMsg);

            return [
                'success' => false,
                'status' => $status,
                'code' => $parsed['code'],
                'admin_message' => "Fadded API ({$url}): {$parsed['admin_message']}",
                'message' => $parsed['admin_message']
            ];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Fadded getProducts timeout/connection error: ' . $e->getMessage());
            return [
                'success' => false,
                'code' => 'CONNECTION_TIMEOUT',
                'admin_message' => 'Unable to connect to Fadded (Timeout). The provider server (api.fadded.net) took too long to respond.',
                'message' => 'Unable to connect to Fadded (Timeout). The provider’s server took too long to respond.'
            ];
        } catch (\Exception $e) {
            Log::error('Fadded getProducts error: ' . $e->getMessage());
            return [
                'success' => false,
                'code' => 'CONNECTION_ERROR',
                'admin_message' => 'Unable to connect to Fadded: ' . $e->getMessage(),
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
                'code' => 'API_KEY_REQUIRED',
                'admin_message' => 'Fadded API key is not configured in Settings.',
                'message' => 'Fadded API key is not configured.'
            ];
        }

        try {
            $url = $this->getEndpointUrl('balance');

            $response = Http::timeout(20)
                ->withHeaders($this->getAuthHeaders(false))
                ->get($url);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            $status = $response->status();
            $code = $response->json('code');
            $rawMsg = $response->json('message');
            $parsed = self::parseProviderError($status, $code, $rawMsg);

            return [
                'success' => false,
                'status' => $status,
                'code' => $parsed['code'],
                'admin_message' => "Fadded API ({$url}): {$parsed['admin_message']}",
                'message' => $parsed['admin_message']
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'code' => 'CONNECTION_ERROR',
                'admin_message' => 'Fadded balance check error: ' . $e->getMessage(),
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Place order for a digital log item via official POST /order
     */
    public function order(string $productKey, int $quantity, string $orderReference): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'code' => 'API_KEY_REQUIRED',
                'admin_message' => 'Fadded API key is not configured in Settings.',
                'customer_message' => 'Unable to complete order with provider at this time. Your wallet balance has been refunded.',
                'message' => 'Unable to complete order with provider at this time. Your wallet balance has been refunded.'
            ];
        }

        try {
            $url = $this->getEndpointUrl('order');

            $payload = [
                'product_key' => $productKey,
                'quantity' => (int)$quantity,
                'external_order_id' => $orderReference,
            ];

            $response = Http::timeout(35)
                ->withHeaders($this->getAuthHeaders(true))
                ->post($url, $payload);

            if ($response->successful()) {
                return $response->json();
            }

            $status = $response->status();
            $code = $response->json('code');
            $rawMsg = $response->json('message');
            $parsed = self::parseProviderError($status, $code, $rawMsg);

            return [
                'success' => false,
                'status' => $status,
                'code' => $parsed['code'],
                'admin_message' => "Fadded order failed ({$url}): {$parsed['admin_message']}",
                'customer_message' => $parsed['customer_message'],
                'message' => $parsed['customer_message']
            ];
        } catch (\Exception $e) {
            Log::error('Fadded order error: ' . $e->getMessage());
            return [
                'success' => false,
                'ambiguous' => true,
                'code' => 'CONNECTION_ERROR',
                'admin_message' => 'Connection to Fadded failed; provider order outcome is unconfirmed: ' . $e->getMessage(),
                'customer_message' => 'The provider outcome is being verified. Your wallet has not been refunded because the order may have been accepted.',
                'message' => 'The provider outcome is being verified. Your wallet has not been refunded because the order may have been accepted.'
            ];
        }
    }
}
