<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsPoolService
{
    protected string $baseUrl = 'https://api.smspool.net';
    protected ?string $apiKey;

    public function __construct()
    {
        $this->apiKey = \App\Models\Setting::get('smspool_api_key');
    }

    /**
     * Get the balance of the SMSPool account.
     * Endpoint: GET /request/balance
     */
    public function getBalance(): array
    {
        $response = Http::timeout(15)->get("{$this->baseUrl}/request/balance", [
            'key' => $this->apiKey,
        ]);

        if ($response->failed()) {
            Log::error('SMSPool getBalance failed', ['response' => $response->body()]);
            $this->handleFailedResponse($response, 'Failed to get balance from SMSPool');
        }

        return $response->json();
    }

    /**
     * Get list of all available countries.
     * Endpoint: GET /country/retrieve_all
     */
    public function getCountries(): array
    {
        $response = Http::timeout(15)->get("{$this->baseUrl}/country/retrieve_all");

        if ($response->failed()) {
            Log::error('SMSPool getCountries failed', ['response' => $response->body()]);
            $this->handleFailedResponse($response, 'Failed to get countries from SMSPool');
        }

        return $response->json();
    }

    /**
     * Get pricing for services in a specific country, or all countries if null.
     * Endpoint: POST /request/pricing
     */
    public function getPricesForCountry(string|int|null $country = null): array
    {
        $params = [
            'key' => $this->apiKey,
        ];

        if ($country !== null) {
            $params['country'] = $country;
        }

        $response = Http::asForm()->timeout(180)->post("{$this->baseUrl}/request/pricing", $params);

        if ($response->failed()) {
            Log::error('SMSPool getPricesForCountry failed', ['country' => $country, 'response' => $response->body()]);
            $this->handleFailedResponse($response, 'Failed to get pricing from SMSPool');
        }

        return $response->json();
    }

    /**
     * Get real-time stock, active price, and success rate for a specific service and country.
     * Endpoint: POST /request/success_rate
     */
    public function getServiceLiveInfo(string|int $service, string|int|null $country = null): ?array
    {
        try {
            $params = [
                'key' => $this->apiKey,
                'service' => $service,
            ];

            if ($country !== null) {
                $params['country'] = $country;
            }

            $response = Http::asForm()->timeout(8)->post("{$this->baseUrl}/request/success_rate", $params);

            if ($response->successful()) {
                $data = $response->json();
                if (is_array($data) && !empty($data)) {
                    $item = null;
                    if ($country !== null && count($data) > 1) {
                        foreach ($data as $row) {
                            if ((string)($row['country_id'] ?? $row['country'] ?? '') === (string)$country) {
                                $item = $row;
                                break;
                            }
                        }
                    }
                    if (!$item) {
                        $item = $data[0];
                    }

                    $lowPrice = isset($item['low_price']) ? (float)$item['low_price'] : null;
                    $standardPrice = isset($item['price']) ? (float)$item['price'] : null;

                    return [
                        'price' => $standardPrice,
                        'low_price' => $lowPrice,
                        'stock' => isset($item['stock']) ? (int)$item['stock'] : 0,
                        'success_rate' => isset($item['success_rate']) ? (int)$item['success_rate'] : null,
                        'name' => $item['name'] ?? null,
                    ];
                }
            }
        } catch (\Throwable $e) {
            Log::warning("SMSPool getServiceLiveInfo error for service {$service}, country {$country}: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Retrieve valid available pools with pricing for a given country and service.
     * Endpoint: POST /pool/retrieve_valid
     */
    public function getValidPools(string|int $country, string|int $service): array
    {
        try {
            $response = Http::asForm()->timeout(10)->post("{$this->baseUrl}/pool/retrieve_valid", [
                'key' => $this->apiKey,
                'country' => $country,
                'service' => $service,
                'web' => 1,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return is_array($data) ? $data : [];
            }
        } catch (\Throwable $e) {
            Log::warning("SMSPool getValidPools failed for country {$country}, service {$service}: " . $e->getMessage());
        }

        return [];
    }

    /**
     * Retrieve all active SMS orders from SMSPool for status checks and timeout reconciliation.
     * Endpoint: POST /request/active
     */
    public function getActiveOrders(): array
    {
        try {
            $response = Http::asForm()->timeout(10)->post("{$this->baseUrl}/request/active", [
                'key' => $this->apiKey,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return is_array($data) ? $data : [];
            }
        } catch (\Throwable $e) {
            Log::warning("SMSPool getActiveOrders failed: " . $e->getMessage());
        }

        return [];
    }

    /**
     * Buy a virtual phone number.
     * Endpoint: POST /purchase/sms
     */
    public function buyNumber(string|int $country, string|int $service, ?float $maxPrice = null, int $pricingOption = 0, ?string $pool = null): array
    {
        $params = [
            'country' => $country,
            'service' => $service,
            'key' => $this->apiKey,
            'pricing_option' => $pricingOption,
        ];

        if ($maxPrice !== null) {
            $params['max_price'] = $maxPrice;
        }

        if ($pool !== null && $pool !== '') {
            $params['pool'] = $pool;
        }

        try {
            $response = Http::asForm()->timeout(15)->post("{$this->baseUrl}/purchase/sms", $params);
        } catch (\Illuminate\Http\Client\ConnectionException $connEx) {
            Log::error('SMSPool buyNumber connection timeout / failure', [
                'country' => $country,
                'service' => $service,
                'error' => $connEx->getMessage()
            ]);
            throw new \App\Exceptions\ProviderTimeoutException("Connection timed out communicating with SMS provider: " . $connEx->getMessage(), 0, $connEx);
        }

        if ($response->failed()) {
            Log::error('SMSPool buyNumber failed', ['country' => $country, 'service' => $service, 'response' => $response->body()]);
            $this->handleFailedResponse($response, 'Failed to purchase number from SMSPool');
        }

        $data = $response->json();

        if (empty($data['success'])) {
            $errorMessage = $data['message'] ?? 'Failed to buy number from SMSPool';
            Log::error('SMSPool buyNumber returned error', ['country' => $country, 'service' => $service, 'data' => $data]);
            $cleanError = html_entity_decode(strip_tags(preg_replace('/<\/p>\s*<p>/i', ' ', $errorMessage)), ENT_QUOTES, 'UTF-8');
            throw new \Exception($cleanError);
        }

        return $data;
    }

    /**
     * Check the status of an active number order / check for SMS.
     * Endpoint: POST /sms/check
     */
    public function checkOrder(string $orderId): array
    {
        $response = Http::asForm()->timeout(15)->post("{$this->baseUrl}/sms/check", [
            'orderid' => $orderId,
            'key' => $this->apiKey,
        ]);

        if ($response->failed()) {
            Log::error('SMSPool checkOrder failed', ['orderid' => $orderId, 'response' => $response->body()]);
            $this->handleFailedResponse($response, 'Failed to check order status from SMSPool');
        }

        return $response->json();
    }

    /**
     * Cancel an active number (and request refund).
     * Endpoint: POST /sms/cancel
     */
    public function cancelOrder(string $orderId): array
    {
        $response = Http::asForm()->timeout(15)->post("{$this->baseUrl}/sms/cancel", [
            'orderid' => $orderId,
            'key' => $this->apiKey,
        ]);

        if ($response->failed()) {
            Log::error('SMSPool cancelOrder failed', ['orderid' => $orderId, 'response' => $response->body()]);
            $this->handleFailedResponse($response, 'Failed to cancel order on SMSPool');
        }

        return $response->json();
    }

    /**
     * Resend / reuse an active number to receive another SMS code.
     * Endpoint: POST /sms/resend
     */
    public function resendOrder(string $orderId): array
    {
        $response = Http::asForm()->timeout(15)->post("{$this->baseUrl}/sms/resend", [
            'orderid' => $orderId,
            'key' => $this->apiKey,
        ]);

        if ($response->failed()) {
            Log::error('SMSPool resendOrder failed', ['orderid' => $orderId, 'response' => $response->body()]);
            $this->handleFailedResponse($response, 'Failed to request SMS resend from SMSPool');
        }

        $data = $response->json();

        if (isset($data['success']) && (int)$data['success'] === 0) {
            $msg = $data['message'] ?? 'Failed to resend SMS on SMSPool';
            throw new \Exception(html_entity_decode(strip_tags($msg), ENT_QUOTES, 'UTF-8'));
        }

        return $data;
    }

    /**
     * Parse and throw a clean exception from a failed response.
     */
    protected function handleFailedResponse(\Illuminate\Http\Client\Response $response, string $defaultMessage): void
    {
        $body = $response->body();
        $data = $response->json();
        
        if (is_array($data) && isset($data['message'])) {
            $msg = $data['message'];
            // Replace paragraph separators with a space
            $msg = preg_replace('/<\/p>\s*<p>/i', ' ', $msg);
            throw new \Exception(html_entity_decode(strip_tags($msg), ENT_QUOTES, 'UTF-8'));
        }
        
        throw new \Exception($body ?: $defaultMessage);
    }
}
