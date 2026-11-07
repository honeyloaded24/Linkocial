<?php

namespace App\Services;

use App\Models\AiProduct;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiPlugService
{
    public const DEFAULT_BASE_URL = 'https://resellers.aiplug.store/api/reseller/v1';

    protected string $baseUrl;
    protected ?string $apiKey;
    protected ?string $webhookSecret;

    public function __construct()
    {
        $dbBaseUrl = null;
        $dbApiKey = null;
        $dbWebhookSecret = null;

        try {
            $dbBaseUrl = Setting::get('aiplug_base_url');
            $dbApiKey = Setting::get('aiplug_api_key');
            $dbWebhookSecret = Setting::get('aiplug_webhook_secret');
        } catch (\Throwable $e) {
            // Safe fallback if settings table is not available yet
        }

        $this->baseUrl = rtrim(($dbBaseUrl ?: config('services.aiplug.base_url', self::DEFAULT_BASE_URL)) ?? self::DEFAULT_BASE_URL, '/');
        $this->apiKey = $dbApiKey ?: config('services.aiplug.api_key');
        $this->webhookSecret = $dbWebhookSecret ?: config('services.aiplug.webhook_secret');
    }

    /**
     * Check if API key is configured.
     */
    public function isConfigured(): bool
    {
        return !empty(trim((string) $this->apiKey));
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }

    public function getWebhookSecret(): ?string
    {
        return $this->webhookSecret;
    }

    /**
     * Make an authenticated HTTP request to AIPlug.
     */
    protected function request(string $method, string $endpoint, array $data = [], array $headers = [])
    {
        if (!$this->isConfigured()) {
            throw new \RuntimeException('AIPlug API key is not configured in Admin Settings.');
        }

        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');

        $defaultHeaders = [
            'Authorization' => 'Bearer ' . trim($this->apiKey),
            'Accept'        => 'application/json',
        ];

        $client = Http::withHeaders(array_merge($defaultHeaders, $headers))
            ->timeout(25)
            ->connectTimeout(10);

        $method = strtolower($method);
        return $client->$method($url, $data);
    }

    /**
     * Retrieve provider wallet balance.
     * Values are returned in Kobo (₦1 = 100 kobo).
     *
     * @return array ['balanceKobo' => int, 'balanceNgn' => float, 'currency' => 'NGN']
     */
    public function getBalance(): array
    {
        $response = $this->request('get', '/balance');

        if (!$response->successful()) {
            Log::warning('AIPlug getBalance failed: ' . $response->body());
            return [
                'success'     => false,
                'message'     => $response->json('message') ?? 'Failed to retrieve balance from provider',
                'balanceKobo' => 0,
                'balanceNgn'  => 0.0,
            ];
        }

        $data = $response->json();
        $balanceKobo = (int) ($data['balanceKobo'] ?? $data['balance'] ?? $data['data']['balanceKobo'] ?? 0);
        $balanceNgn = round($balanceKobo / 100, 2);

        return [
            'success'     => true,
            'balanceKobo' => $balanceKobo,
            'balanceNgn'  => $balanceNgn,
            'currency'    => 'NGN',
            'raw'         => $data,
        ];
    }

    /**
     * Fetch the live catalog of products from AIPlug.
     */
    public function getCatalog(): array
    {
        $response = $this->request('get', '/catalog');

        if (!$response->successful()) {
            Log::error('AIPlug getCatalog failed: ' . $response->body());
            return [
                'success'  => false,
                'message'  => $response->json('message') ?? 'Failed to fetch catalog',
                'products' => [],
            ];
        }

        $json = $response->json();
        // Support flexible wrapper shapes: [ {..} ] or { "products": [..] } or { "data": [..] }
        $products = [];
        if (isset($json['products']) && is_array($json['products'])) {
            $products = $json['products'];
        } elseif (isset($json['data']) && is_array($json['data'])) {
            $products = $json['data'];
        } elseif (is_array($json) && array_is_list($json)) {
            $products = $json;
        }

        return [
            'success'  => true,
            'products' => $products,
        ];
    }

    /**
     * Synchronize AIPlug catalog into local ai_products database table.
     * Computes wholesale price (kobo / 100) and retail selling price with markup.
     */
    public function syncCatalog(): array
    {
        $catalogResult = $this->getCatalog();

        if (!$catalogResult['success']) {
            return $catalogResult;
        }

        $products = $catalogResult['products'];
        $syncedCount = 0;
        $updatedCount = 0;

        $globalMarkup = (float) Setting::get('ai_marketplace_markup', '20');

        foreach ($products as $p) {
            if (!is_array($p)) {
                continue;
            }
            $providerId = (string) ($p['productId'] ?? $p['id'] ?? '');
            if (empty($providerId)) {
                continue;
            }

            $name = (string) ($p['name'] ?? $p['title'] ?? 'Google AI Service');
            $category = (string) ($p['category'] ?? 'Google AI');
            $description = (string) ($p['description'] ?? '');
            $icon = (string) ($p['icon'] ?? $p['image'] ?? 'fa-solid fa-wand-magic-sparkles');

            // Price in Kobo (₦1 = 100 kobo)
            $priceKobo = (int) ($p['priceKobo'] ?? $p['price_kobo'] ?? $p['costKobo'] ?? (!empty($p['price']) ? round(((float) $p['price']) * 100) : 0));
            $wholesaleNgn = round($priceKobo / 100, 2);

            $inStock = (bool) ($p['inStock'] ?? $p['available'] ?? true);
            $deliveryType = (string) ($p['deliveryType'] ?? $p['delivery_type'] ?? 'automatic');

            $existing = AiProduct::where('provider_product_id', $providerId)->first();

            if ($existing) {
                // If admin hasn't set a custom retail price override, update with global markup
                $existingWholesale = (float) $existing->wholesale_price;
                $retailPrice = (float) $existing->retail_price;

                if ($retailPrice <= 0 || abs($existingWholesale - $wholesaleNgn) > 0.01) {
                    $retailPrice = round($wholesaleNgn * (1 + ($globalMarkup / 100)), 2);
                }

                $existing->update([
                    'name'                 => $name,
                    'category'             => $category,
                    'description'          => $description ?: $existing->description,
                    'icon'                 => $icon ?: $existing->icon,
                    'wholesale_price_kobo' => $priceKobo,
                    'wholesale_price'      => $wholesaleNgn,
                    'retail_price'         => $retailPrice,
                    'in_stock'             => $inStock,
                    'delivery_type'        => $deliveryType,
                    'metadata'             => $p,
                ]);

                $updatedCount++;
            } else {
                $retailPrice = round($wholesaleNgn * (1 + ($globalMarkup / 100)), 2);

                AiProduct::create([
                    'provider_product_id'  => $providerId,
                    'name'                 => $name,
                    'category'             => $category,
                    'description'          => $description,
                    'icon'                 => $icon,
                    'wholesale_price_kobo' => $priceKobo,
                    'wholesale_price'      => $wholesaleNgn,
                    'retail_price'         => $retailPrice,
                    'in_stock'             => $inStock,
                    'delivery_type'        => $deliveryType,
                    'is_active'            => true,
                    'metadata'             => $p,
                ]);

                $syncedCount++;
            }
        }

        return [
            'success'      => true,
            'message'      => "Catalog sync complete. Synced {$syncedCount} new, updated {$updatedCount} products.",
            'synced_count' => $syncedCount,
            'updated_count'=> $updatedCount,
            'total'        => count($products),
        ];
    }

    /**
     * Place an order with AIPlug.
     *
     * @param string $productId Provider product ID (e.g. "ext:67")
     * @param int $quantity Quantity to purchase
     * @param string|null $idempotencyKey Unique request key
     * @param string $deliveryMode 'portal_only'
     * @return array Response from AIPlug
     */
    public function placeOrder(string $productId, int $quantity = 1, ?string $idempotencyKey = null, string $deliveryMode = 'portal_only'): array
    {
        $idempotencyKey = $idempotencyKey ?: ('aip_' . time() . '_' . bin2hex(random_bytes(4)));

        $payload = [
            'items' => [
                [
                    'productId' => $productId,
                    'quantity'  => (int) $quantity,
                ],
            ],
            'deliveryMode' => $deliveryMode,
        ];

        $headers = [
            'Idempotency-Key' => $idempotencyKey,
            'Content-Type'    => 'application/json',
        ];

        $response = $this->request('post', '/orders', $payload, $headers);

        if (!$response->successful()) {
            Log::error('AIPlug placeOrder failed: ' . $response->body(), [
                'payload' => $payload,
                'status'  => $response->status(),
            ]);

            return [
                'success' => false,
                'status'  => $response->status(),
                'message' => $response->json('message') ?? 'Provider failed to process the AI order.',
                'raw'     => $response->json(),
            ];
        }

        return [
            'success' => true,
            'data'    => $response->json(),
        ];
    }

    /**
     * Retrieve order details and latest delivery state from AIPlug.
     *
     * @param string $orderId AIPlug order ID (e.g. "AIP-R-...")
     * @return array
     */
    public function getOrder(string $orderId): array
    {
        $response = $this->request('get', '/orders/' . urlencode($orderId));

        if (!$response->successful()) {
            Log::warning("AIPlug getOrder({$orderId}) failed: " . $response->body());
            return [
                'success' => false,
                'message' => $response->json('message') ?? 'Failed to fetch order details',
            ];
        }

        return [
            'success' => true,
            'data'    => $response->json(),
        ];
    }

    /**
     * Register or update the webhook endpoint URL at AIPlug.
     *
     * @param string $webhookUrl
     * @return array Contains signingSecret upon creation/rotation
     */
    public function registerWebhook(string $webhookUrl): array
    {
        $response = $this->request('put', '/webhooks', [
            'url' => $webhookUrl,
        ]);

        if (!$response->successful()) {
            return [
                'success' => false,
                'message' => $response->json('message') ?? 'Failed to register webhook URL with provider',
            ];
        }

        $json = $response->json();
        $signingSecret = $json['signingSecret'] ?? $json['data']['signingSecret'] ?? null;

        if ($signingSecret) {
            Setting::set('aiplug_webhook_secret', $signingSecret);
            $this->webhookSecret = $signingSecret;
        }

        return [
            'success'        => true,
            'message'        => 'Webhook URL registered successfully.',
            'signing_secret' => $signingSecret,
            'data'           => $json,
        ];
    }

    /**
     * Verify the X-AIPlug-Signature header on incoming webhook requests.
     * Format: t=UNIX_SECONDS,v1=HEX_HMAC
     * HMAC-SHA256(timestamp . '.' . rawBody, signingSecret)
     */
    public function verifyWebhookSignature(string $rawBody, ?string $signatureHeader): bool
    {
        if (empty($signatureHeader) || empty($this->webhookSecret)) {
            Log::warning('AIPlug webhook verification failed: missing signature header or webhook secret.');
            return false;
        }

        // Match t=1234567890,v1=abcde...
        if (!preg_match('/^t=(\d+),v1=([a-f0-9]{64})$/', trim($signatureHeader), $matches)) {
            Log::warning('AIPlug webhook verification failed: signature header does not match t=...,v1=... format.');
            return false;
        }

        $timestamp = (int) $matches[1];
        $receivedHmac = $matches[2];

        // Reject timestamps older than 5 minutes (300 seconds) or in the future by > 300 seconds
        if (abs(time() - $timestamp) > 300) {
            Log::warning("AIPlug webhook verification failed: timestamp drift too large ({$timestamp} vs current " . time() . ").");
            return false;
        }

        // Compute HMAC-SHA256: secret is whsec_...
        $expectedHmac = hash_hmac('sha256', $timestamp . '.' . $rawBody, $this->webhookSecret);

        if (!hash_equals($expectedHmac, $receivedHmac)) {
            Log::warning('AIPlug webhook verification failed: computed HMAC does not match received HMAC.');
            return false;
        }

        return true;
    }
}
