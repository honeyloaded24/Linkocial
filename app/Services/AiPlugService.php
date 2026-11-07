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
        $this->reloadSettings();
    }

    /**
     * Reload API configuration from database settings / config.
     */
    public function reloadSettings(): void
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
     * Extract product wholesale price in Kobo (₦1 = 100 kobo) from an AIPlug catalog item.
     * Supports `resellerPriceKobo` (primary AIPlug Reseller API field) as well as fallback keys.
     */
    public static function extractPriceKobo(array $p): int
    {
        $koboKeys = [
            'resellerPriceKobo',
            'reseller_price_kobo',
            'wholesalePriceKobo',
            'wholesale_price_kobo',
            'unitPriceKobo',
            'unit_price_kobo',
            'priceKobo',
            'price_kobo',
            'costKobo',
            'cost_kobo',
            'amountKobo',
            'amount_kobo',
            'totalKobo',
            'total_kobo',
        ];

        foreach ($koboKeys as $key) {
            if (isset($p[$key]) && is_numeric($p[$key]) && (float) $p[$key] > 0) {
                return (int) round((float) $p[$key]);
            }
        }

        if (isset($p['pricing']) && is_array($p['pricing'])) {
            foreach ($koboKeys as $key) {
                if (isset($p['pricing'][$key]) && is_numeric($p['pricing'][$key]) && (float) $p['pricing'][$key] > 0) {
                    return (int) round((float) $p['pricing'][$key]);
                }
            }
        }

        $nairaKeys = [
            'resellerPrice',
            'reseller_price',
            'wholesalePrice',
            'wholesale_price',
            'unitPrice',
            'unit_price',
            'unit_price_ngn',
            'priceNgn',
            'price_ngn',
            'price',
            'cost',
        ];

        foreach ($nairaKeys as $key) {
            if (isset($p[$key]) && is_numeric($p[$key]) && (float) $p[$key] > 0) {
                return (int) round(((float) $p[$key]) * 100);
            }
        }

        return 0;
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
                'message'     => $response->json('message') ?? $response->json('error') ?? 'Failed to retrieve balance from provider',
                'balanceKobo' => 0,
                'balanceNgn'  => 0.0,
            ];
        }

        $data = $response->json();
        $balanceKobo = (int) (
            $data['balanceKobo']
            ?? $data['balance_kobo']
            ?? $data['wallet']['balanceKobo']
            ?? $data['wallet']['balance_kobo']
            ?? $data['data']['balanceKobo']
            ?? $data['data']['balance_kobo']
            ?? $data['balance']
            ?? 0
        );
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
                'message'  => $response->json('message') ?? $response->json('error') ?? 'Failed to fetch catalog',
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
        $this->reloadSettings();
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
            $rawCategory = trim((string) ($p['category'] ?? ''));
            $category = (!empty($rawCategory) && strtolower($rawCategory) !== 'google ai')
                ? $rawCategory
                : self::inferCategory($name, $p);
            $description = (string) ($p['description'] ?? $p['summary'] ?? '');
            $rawIcon = trim((string) ($p['icon'] ?? ''));
            $icon = (!empty($rawIcon) && $rawIcon !== 'fa-solid fa-wand-magic-sparkles')
                ? $rawIcon
                : self::inferIcon($name, $category);

            // Price in Kobo (₦1 = 100 kobo) — AIPlug Reseller API uses `resellerPriceKobo`
            $priceKobo = self::extractPriceKobo($p);
            $wholesaleNgn = round($priceKobo / 100, 2);

            if (isset($p['inStock']) || isset($p['in_stock']) || isset($p['available'])) {
                $inStock = (bool) ($p['inStock'] ?? $p['in_stock'] ?? $p['available']);
            } elseif (array_key_exists('stock', $p)) {
                $inStock = $p['stock'] === null || (int) $p['stock'] > 0;
            } else {
                $inStock = true;
            }

            $stockCount = (array_key_exists('stock', $p) && $p['stock'] !== null) ? (int) $p['stock'] : 999;

            if (!empty($p['deliveryType']) || !empty($p['delivery_type'])) {
                $deliveryType = (string) ($p['deliveryType'] ?? $p['delivery_type']);
            } elseif (array_key_exists('autoFulfill', $p)) {
                $deliveryType = $p['autoFulfill'] ? 'automatic' : 'manual';
            } else {
                $deliveryType = 'automatic';
            }

            $existing = AiProduct::where('provider_product_id', $providerId)->first();

            if ($existing) {
                $existingWholesale = (float) $existing->getRawOriginal('wholesale_price');
                $retailPrice = (float) $existing->getRawOriginal('retail_price');

                if ($retailPrice <= 0 || $existingWholesale <= 0 || abs($existingWholesale - $wholesaleNgn) > 0.01) {
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
                    'stock_count'          => $stockCount,
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
                    'stock_count'          => $stockCount,
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

    /**
     * Automatically classify an AI product into a user-friendly category based on its name/metadata.
     */
    public static function inferCategory(string $name, array $payload = []): string
    {
        $haystack = strtolower($name . ' ' . ($payload['summary'] ?? '') . ' ' . ($payload['description'] ?? ''));

        if (preg_match('/\b(capcut|veo3|veo|heygen|elevenlabs|sora|runway|pika|kling|midjourney|video|voice|audio)\b/i', $haystack)) {
            return 'Video, Audio & Voice';
        }
        if (preg_match('/\b(canva|figma|autodesk|meitu|adobe|photoshop|illustrator|design|creative)\b/i', $haystack)) {
            return 'Design & Creative';
        }
        if (preg_match('/\b(bolt\.new|bolt|lovable|replit|warp|railway|resend|cursor|copilot|claude code|windsurf|v0|coding|developer)\b/i', $haystack)) {
            return 'Coding & Dev Tools';
        }
        if (preg_match('/\b(duolingo|hanzii|coursera|udemy|quizlet|chegg|skillshare)\b/i', $haystack)) {
            return 'Education & Language';
        }
        if (preg_match('/\b(telegram|gmail|nordvpn|surfshark|vpn|mail|youtube)\b/i', $haystack)) {
            return 'Social, Mail & VPN';
        }
        if (preg_match('/\b(notion|grammarly|microsoft 365|office 365|zoom|linkedin|windows key|windows 11|windows 10)\b/i', $haystack)) {
            return 'Productivity & Business';
        }

        return 'AI Assistants & API';
    }

    /**
     * Automatically pick a recognizable FontAwesome icon for an AI product.
     */
    public static function inferIcon(string $name, string $category = ''): string
    {
        $n = strtolower($name);

        if (str_contains($n, 'chatgpt') || str_contains($n, 'openai')) {
            return 'fa-solid fa-robot';
        }
        if (str_contains($n, 'gemini') || str_contains($n, 'google') || str_contains($n, 'veo')) {
            return 'fa-brands fa-google';
        }
        if (str_contains($n, 'capcut') || str_contains($n, 'heygen')) {
            return 'fa-solid fa-clapperboard';
        }
        if (str_contains($n, 'elevenlabs')) {
            return 'fa-solid fa-microphone-lines';
        }
        if (str_contains($n, 'canva') || str_contains($n, 'meitu')) {
            return 'fa-solid fa-palette';
        }
        if (str_contains($n, 'figma')) {
            return 'fa-brands fa-figma';
        }
        if (str_contains($n, 'autodesk')) {
            return 'fa-solid fa-compass-drafting';
        }
        if (str_contains($n, 'bolt') || str_contains($n, 'lovable') || str_contains($n, 'replit') || str_contains($n, 'warp') || str_contains($n, 'railway') || str_contains($n, 'resend')) {
            return 'fa-solid fa-terminal';
        }
        if (str_contains($n, 'kimi') || str_contains($n, 'qwen') || str_contains($n, 'muse') || str_contains($n, 'antigravity') || str_contains($n, 'gamma')) {
            return 'fa-solid fa-brain';
        }
        if (str_contains($n, 'telegram')) {
            return 'fa-brands fa-telegram';
        }
        if (str_contains($n, 'linkedin')) {
            return 'fa-brands fa-linkedin';
        }
        if (str_contains($n, 'gmail')) {
            return 'fa-solid fa-envelope-open-text';
        }
        if (str_contains($n, 'nordvpn') || str_contains($n, 'surfshark') || str_contains($n, 'vpn')) {
            return 'fa-solid fa-user-shield';
        }
        if (str_contains($n, 'microsoft') || str_contains($n, 'windows')) {
            return 'fa-brands fa-microsoft';
        }
        if (str_contains($n, 'notion') || str_contains($n, 'grammarly')) {
            return 'fa-solid fa-pen-nib';
        }
        if (str_contains($n, 'zoom')) {
            return 'fa-solid fa-video';
        }
        if (str_contains($n, 'duolingo') || str_contains($n, 'hanzii')) {
            return 'fa-solid fa-language';
        }

        return match ($category) {
            'Video, Audio & Voice'    => 'fa-solid fa-film',
            'Design & Creative'       => 'fa-solid fa-palette',
            'Coding & Dev Tools'      => 'fa-solid fa-code',
            'Productivity & Business' => 'fa-solid fa-briefcase',
            'Social, Mail & VPN'      => 'fa-solid fa-shield-halved',
            'Education & Language'    => 'fa-solid fa-graduation-cap',
            default                   => 'fa-solid fa-wand-magic-sparkles',
        };
    }
}
