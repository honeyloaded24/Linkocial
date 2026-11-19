<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PixelSubApiService
{
    private const BASE_URL = 'https://pixelsub-production-f248.up.railway.app/api/v1';
    private const TIMEOUT = 30;

    private string $apiKey;

    public function __construct()
    {
        $this->apiKey = (string) Setting::get('pixelsub_api_key', '');
    }

    public function getProducts(): array { return $this->request('GET', '/products'); }
    public function getStock(): array { return $this->request('GET', '/stock'); }
    public function checkBalance(): array { return $this->request('GET', '/balance'); }
    public function getOrders(array $params = []): array { return $this->request('GET', '/orders', $params); }

    public function createOrder(string $productId, int $quantity, string $reference): array
    {
        return $this->request('POST', '/orders', [
            'productId' => (int) $productId,
            'quantity' => $quantity,
            'reference' => $reference,
        ]);
    }

    public function getOrder(string $idOrReference): array
    {
        return $this->request('GET', '/orders/' . rawurlencode($idOrReference));
    }

    public function testConnection(): bool
    {
        return $this->apiKey !== '' && (bool) ($this->checkBalance()['success'] ?? false);
    }

    private function request(string $method, string $endpoint, array $data = []): array
    {
        if ($this->apiKey === '') {
            return ['success' => false, 'error_code' => 'NO_API_KEY', 'error_message' => 'PixelSub API key is not configured.'];
        }

        try {
            $http = Http::withHeaders([
                'X-API-Key' => $this->apiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->timeout(self::TIMEOUT);

            $response = $method === 'POST'
                ? $http->post(self::BASE_URL . $endpoint, $data)
                : $http->get(self::BASE_URL . $endpoint, $data);

            $json = $response->json() ?? [];
            if (!$response->successful()) {
                Log::warning('PixelSub API non-2xx response', [
                    'endpoint' => $endpoint,
                    'status' => $response->status(),
                    'body_snippet' => substr($response->body(), 0, 300),
                ]);
                return [
                    'success' => false,
                    'error_code' => $response->status(),
                    'error_message' => $json['message'] ?? match ($response->status()) {
                        401 => 'Invalid or disabled API key.',
                        402 => 'Provider wallet balance is insufficient.',
                        404 => 'Provider product or order not found.',
                        409 => 'Provider stock is insufficient.',
                        429 => 'Provider rate limit reached. Please retry shortly.',
                        default => 'Provider request failed.',
                    },
                    'http_status' => $response->status(),
                    'raw' => $json,
                ];
            }

            return ['success' => true, 'data' => $json['data'] ?? $json, 'raw' => $json];
        } catch (\Throwable $e) {
            Log::error('PixelSub API request failed', ['endpoint' => $endpoint, 'error' => $e->getMessage()]);
            return ['success' => false, 'ambiguous' => $method === 'POST', 'error_message' => $method === 'POST'
                ? 'Provider submission outcome is unknown. Reconcile using the original reference before retrying.'
                : 'Provider communication failed.'];
        }
    }
}
