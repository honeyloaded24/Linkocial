<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class AlaricApiService
{
    private const BASE_URL = 'https://api.alaricstore.com/api/v1';
    private const TIMEOUT  = 30;

    private string $apiKey;

    public function __construct()
    {
        $this->apiKey = (string) Setting::get('alaric_api_key', '');
    }

    // ─── Public API methods ────────────────────────────────────────────────────

    /**
     * Check provider wallet balance.
     */
    public function checkBalance(): array
    {
        try {
            return $this->makeRequest('GET', '/balance');
        } catch (\Throwable $e) {
            Log::error('AlaricApi: checkBalance failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'error_message' => 'Failed to retrieve balance.'];
        }
    }

    /**
     * Retrieve all products from Alaric.
     */
    public function getProducts(): array
    {
        try {
            return $this->makeRequest('GET', '/products');
        } catch (\Throwable $e) {
            Log::error('AlaricApi: getProducts failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'data' => [], 'error_message' => $e->getMessage()];
        }
    }

    /**
     * Create a provider order (purchase).
     *
     * IMPORTANT: Generate $idempotencyKey BEFORE calling this method.
     * Store it in the database with the internal order.
     * Pass the SAME key on any retry — never generate a new one on timeout.
     *
     * @param string $productId       Alaric product ID
     * @param int    $quantity        Number of units
     * @param float  $maxTotal        Max acceptable USDT total (safety cap — not the NGN price)
     * @param string $idempotencyKey  Unique UUID generated before first call
     */
    public function createOrder(
        string $productId,
        int    $quantity,
        float  $maxTotal,
        string $idempotencyKey
    ): array {
        $body = [
            'product_id' => $productId,
            'quantity'   => $quantity,
            'max_total'  => $maxTotal,
        ];

        try {
            return $this->makeRequest('POST', '/orders', $body, [
                'Idempotency-Key' => $idempotencyKey,
            ]);
        } catch (\Throwable $e) {
            $timedOut = str_contains(strtolower($e->getMessage()), 'timed out')
                     || str_contains(strtolower($e->getMessage()), 'timeout');

            // Any exception during order submission may happen after the provider
            // accepted the request. Only explicit HTTP rejection responses are
            // treated as definitive failures; transport exceptions are ambiguous.
            $ambiguous = true;

            Log::error('AlaricApi: createOrder failed', [
                'product_id'      => $productId,
                'quantity'        => $quantity,
                'idempotency_key' => $idempotencyKey,
                'timed_out'       => $timedOut,
                'ambiguous'       => $ambiguous,
                'error'           => $e->getMessage(),
            ]);

            return [
                'success'       => false,
                'timed_out'     => $timedOut,
                'ambiguous'     => $ambiguous,
                'error_message' => 'Provider communication failed. The order outcome is unknown — do not retry with a new key.',
            ];
        }
    }

    /**
     * Retrieve provider order status and delivered items.
     */
    public function getOrder(string $orderId): array
    {
        try {
            return $this->makeRequest('GET', "/orders/{$orderId}");
        } catch (\Throwable $e) {
            Log::error('AlaricApi: getOrder failed', [
                'order_id' => $orderId,
                'error'    => $e->getMessage(),
            ]);
            return ['success' => false, 'error_message' => $e->getMessage()];
        }
    }

    /**
     * Retrieve provider order history.
     */
    public function getOrders(array $params = []): array
    {
        try {
            return $this->makeRequest('GET', '/orders', $params);
        } catch (\Throwable $e) {
            Log::error('AlaricApi: getOrders failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'data' => [], 'error_message' => $e->getMessage()];
        }
    }

    /**
     * Test API connectivity — returns true if key is valid and API is reachable.
     */
    public function testConnection(): bool
    {
        if (empty($this->apiKey)) {
            return false;
        }

        $result = $this->checkBalance();
        return (bool) ($result['success'] ?? false);
    }

    // ─── Internal ──────────────────────────────────────────────────────────────

    /**
     * Core HTTP request handler.
     * Never logs the API key or customer credentials.
     */
    private function makeRequest(
        string $method,
        string $endpoint,
        array  $data         = [],
        array  $extraHeaders = []
    ): array {
        if (empty($this->apiKey)) {
            return [
                'success'       => false,
                'error_code'    => 'NO_API_KEY',
                'error_message' => 'Alaric API key is not configured. Contact support.',
            ];
        }

        $headers = array_merge([
            'X-API-Key'    => $this->apiKey,
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ], $extraHeaders);

        $http = Http::withHeaders($headers)->timeout(self::TIMEOUT);
        $url  = self::BASE_URL . $endpoint;

        $response = match (strtoupper($method)) {
            'GET'   => $http->get($url, $data),
            'POST'  => $http->post($url, $data),
            'PATCH' => $http->patch($url, $data),
            default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}"),
        };

        // Log non-2xx — sanitised, never including the key
        if (!$response->successful()) {
            Log::warning('AlaricApi: non-2xx response', [
                'endpoint'     => $endpoint,
                'method'       => $method,
                'status'       => $response->status(),
                'body_snippet' => substr($response->body(), 0, 300),
            ]);
        }

        // Rate limiting
        if ($response->status() === 429) {
            $retryAfter = (int) $response->header('Retry-After', 60);
            return [
                'success'       => false,
                'error_code'    => 'RATE_LIMITED',
                'error_message' => "Rate limited by provider. Retry after {$retryAfter} seconds.",
                'retry_after'   => $retryAfter,
            ];
        }

        // Maintenance window
        if ($response->status() === 503) {
            return [
                'success'       => false,
                'error_code'    => 'SERVICE_UNAVAILABLE',
                'error_message' => 'Provider is temporarily unavailable. Please try again shortly.',
            ];
        }

        $json = $response->json() ?? [];

        if ($response->successful()) {
            return [
                'success' => true,
                'data'    => $json['data'] ?? $json,
                'raw'     => $json,
            ];
        }

        return [
            'success'       => false,
            'error_code'    => $json['error'] ?? $json['code'] ?? 'UNKNOWN',
            'error_message' => $json['message'] ?? 'An unexpected error occurred with the provider.',
            'http_status'   => $response->status(),
        ];
    }
}
