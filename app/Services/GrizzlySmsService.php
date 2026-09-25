<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GrizzlySmsService
{
    protected string $baseUrl = 'https://api.grizzlysms.com/stubs/handler_api.php';
    protected ?string $apiKey;

    /**
     * Map of common service codes to readable names for Grizzly SMS.
     */
    protected array $serviceDictionary = [
        'tg' => 'Telegram',
        'wa' => 'WhatsApp',
        'go' => 'Google / Gmail / YouTube',
        'ig' => 'Instagram',
        'fb' => 'Facebook',
        'tw' => 'Twitter / X',
        'vi' => 'Viber',
        'oi' => 'Tinder',
        'ds' => 'Discord',
        'nf' => 'Netflix',
        'tk' => 'TikTok',
        'mb' => 'Yahoo',
        'ub' => 'Uber',
        'wb' => 'WeChat',
        'sn' => 'Snapchat',
        'am' => 'Amazon',
        'li' => 'LinkedIn',
        'me' => 'Line',
        'pm' => 'AOL',
        'hw' => 'AliPay / Alibaba',
        'kt' => 'KakaoTalk',
        'dr' => 'OpenTable',
        'ya' => 'Yandex',
        'vk' => 'VKontakte',
        'ok' => 'Odnoklassniki',
        'ma' => 'Mail.ru',
        'bl' => 'Bigo Live',
        'qq' => 'QQ',
        'ot' => 'Any Other Service',
    ];

    public function __construct()
    {
        $this->apiKey = \App\Models\Setting::get('grizzly_api_key', env('GRIZZLY_API_KEY'));
    }

    /**
     * Get account balance from Grizzly SMS.
     */
    public function getBalance(): array
    {
        if (empty($this->apiKey)) {
            return ['status' => false, 'balance' => 0.00, 'message' => 'Grizzly API key is not configured.'];
        }

        try {
            $response = Http::timeout(15)->get($this->baseUrl, [
                'api_key' => $this->apiKey,
                'action' => 'getBalance',
            ]);

            $body = trim($response->body());
            if (str_starts_with($body, 'ACCESS_BALANCE:')) {
                $balance = (float) substr($body, strlen('ACCESS_BALANCE:'));
                return ['status' => true, 'balance' => $balance];
            }

            return ['status' => false, 'balance' => 0.00, 'error' => $body];
        } catch (\Throwable $e) {
            Log::error('Grizzly SMS getBalance failed: ' . $e->getMessage());
            return ['status' => false, 'balance' => 0.00, 'error' => $e->getMessage()];
        }
    }

    /**
     * Retrieve supported countries list from Grizzly SMS.
     */
    public function getCountries(): array
    {
        if (!empty($this->apiKey)) {
            try {
                $response = Http::timeout(20)->get($this->baseUrl, [
                    'api_key' => $this->apiKey,
                    'action' => 'getCountries',
                ]);

                if ($response->successful()) {
                    $json = $response->json();
                    if (is_array($json) && !empty($json)) {
                        $list = [];
                        foreach ($json as $id => $countryData) {
                            $cId = is_array($countryData) && isset($countryData['id']) ? $countryData['id'] : $id;
                            $name = is_array($countryData) ? ($countryData['eng'] ?? $countryData['name'] ?? "Country #{$cId}") : (string)$countryData;
                            $list[] = [
                                'ID' => (string) $cId,
                                'name' => (string) $name,
                                'cc' => (string) ($countryData['prefix'] ?? $countryData['code'] ?? '')
                            ];
                        }
                        if (!empty($list)) {
                            return $list;
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Grizzly getCountries API call failed, using fallback list: ' . $e->getMessage());
            }
        }

        // Standard country dictionary fallback
        return $this->getDefaultCountryList();
    }

    /**
     * Get pricing and services for a given country code on Grizzly SMS.
     */
    public function getPricesForCountry(string|int $country): array
    {
        if (empty($this->apiKey)) {
            return [];
        }

        try {
            $params = [
                'api_key' => $this->apiKey,
                'action' => 'getPrices',
                'country' => $country,
            ];

            $response = Http::timeout(25)->get($this->baseUrl, $params);

            if ($response->failed()) {
                Log::error('Grizzly getPrices failed: ' . $response->body());
                return [];
            }

            $data = $response->json();
            if (!is_array($data)) {
                return [];
            }

            // Structure is typically: { "countryId": { "serviceCode": { "cost": 15.5, "count": 120 } } }
            $countryData = $data[(string)$country] ?? $data[(int)$country] ?? $data;
            if (!is_array($countryData)) {
                return [];
            }

            $items = [];
            foreach ($countryData as $code => $val) {
                if (!is_array($val)) {
                    continue;
                }

                $cost = isset($val['cost']) ? (float)$val['cost'] : (isset($val['price']) ? (float)$val['price'] : null);
                $count = isset($val['count']) ? (int)$val['count'] : 1;

                if ($cost === null || $cost <= 0) {
                    continue;
                }

                // If count is zero, skip out-of-stock
                if ($count <= 0) {
                    continue;
                }

                $serviceName = $this->serviceDictionary[strtolower($code)] ?? strtoupper($code);

                $items[] = [
                    'service' => (string) $code,
                    'service_name' => $serviceName,
                    'price' => $cost,
                    'count' => $count,
                ];
            }

            return $items;
        } catch (\Throwable $e) {
            Log::error('Grizzly getPricesForCountry exception: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Purchase a virtual number from Grizzly SMS.
     */
    public function buyNumber(string|int $country, string $service, ?float $maxPrice = null): array
    {
        if (empty($this->apiKey)) {
            throw new \Exception('Grizzly SMS API key is not configured. Please contact support.');
        }

        $params = [
            'api_key' => $this->apiKey,
            'action' => 'getNumberV2',
            'country' => $country,
            'service' => $service,
        ];

        if ($maxPrice !== null) {
            $params['maxPrice'] = $maxPrice;
        }

        $response = Http::timeout(25)->get($this->baseUrl, $params);
        $body = trim($response->body());

        // Check if returned standard error string
        if ($body === 'NO_NUMBERS') {
            throw new \Exception('No numbers currently available for this service and country on Server 2. Please try again or select another service.');
        }
        if ($body === 'NO_BALANCE') {
            throw new \Exception('Server 2 provider balance is temporarily depleted. Please use Server 1 or contact admin.');
        }
        if ($body === 'BAD_KEY') {
            throw new \Exception('Invalid Server 2 API configuration.');
        }
        if (str_contains($body, 'SERVICE_UNAVAILABLE') || str_contains($body, 'prohibited')) {
            throw new \Exception('This service is currently restricted in this country.');
        }

        // Try parsing JSON (getNumberV2 response)
        $data = $response->json();
        if (is_array($data) && isset($data['activationId']) && isset($data['phoneNumber'])) {
            $serviceName = $this->serviceDictionary[strtolower($service)] ?? strtoupper($service);
            return [
                'success' => 1,
                'order_id' => (string)$data['activationId'],
                'phonenumber' => (string)$data['phoneNumber'],
                'cost' => (isset($data['activationCost']) && (float)$data['activationCost'] > 0) ? (float)$data['activationCost'] : null,
                'country' => (string)($data['countryCode'] ?? $country),
                'service' => $serviceName,
                'time_left' => 1200,
            ];
        }

        // Fallback: parse V1 string response format: ACCESS_NUMBER:38496653:66846426435
        if (str_starts_with($body, 'ACCESS_NUMBER:')) {
            $parts = explode(':', $body);
            if (count($parts) >= 3) {
                $serviceName = $this->serviceDictionary[strtolower($service)] ?? strtoupper($service);
                return [
                    'success' => 1,
                    'order_id' => (string)$parts[1],
                    'phonenumber' => (string)$parts[2],
                    'cost' => null, // Controller will safely use the quoted wholesale price
                    'country' => (string)$country,
                    'service' => $serviceName,
                    'time_left' => 1200,
                ];
            }
        }

        Log::error('Grizzly buyNumber unexpected response: ' . $body);
        throw new \Exception($body ?: 'Failed to order number from Server 2.');
    }

    /**
     * Check status of an active number order / poll for SMS.
     * Statuses matching SMSPool format:
     * 1 = pending
     * 3 = completed / received
     * 5/6 = canceled / refunded
     */
    public function checkOrder(string|int $orderId): array
    {
        if (empty($this->apiKey)) {
            return ['status' => 1];
        }

        try {
            $response = Http::timeout(15)->get($this->baseUrl, [
                'api_key' => $this->apiKey,
                'action' => 'getStatus',
                'id' => $orderId,
            ]);

            $body = trim($response->body());

            // STATUS_WAIT_CODE or STATUS_WAIT_RESEND
            if ($body === 'STATUS_WAIT_CODE' || $body === 'STATUS_WAIT_RESEND' || $body === 'STATUS_WAIT_RETRY') {
                return ['status' => 1];
            }

            // STATUS_OK:<code>
            if (str_starts_with($body, 'STATUS_OK:')) {
                $code = trim(substr($body, strlen('STATUS_OK:')));
                return [
                    'status' => 3,
                    'sms' => $code,
                    'full_sms' => "Your verification code is: {$code}"
                ];
            }

            // STATUS_CANCEL or NO_ACTIVATION (purged/expired by provider)
            if ($body === 'STATUS_CANCEL' || $body === 'NO_ACTIVATION') {
                return ['status' => 5];
            }

            return ['status' => 1];
        } catch (\Throwable $e) {
            Log::error("Grizzly checkOrder error: " . $e->getMessage());
            return ['status' => 1];
        }
    }

    /**
     * Cancel an active number on Grizzly SMS (releases number and refunds).
     * Action: setStatus, status=8
     */
    public function cancelOrder(string|int $orderId): array
    {
        if (empty($this->apiKey)) {
            return ['success' => true];
        }

        try {
            $response = Http::timeout(15)->get($this->baseUrl, [
                'api_key' => $this->apiKey,
                'action' => 'setStatus',
                'id' => $orderId,
                'status' => 8, // 8 = cancel
            ]);

            $body = trim($response->body());
            Log::info("Grizzly cancelOrder result: {$body}");

            if (str_contains($body, 'EARLY_CANCEL_DENIED')) {
                throw new \Exception('Provider requires a 2-minute waiting period before cancellation can be accepted.');
            }

            if (str_contains($body, 'ACCESS_CANCEL') || str_contains($body, 'ACCESS') || $body === 'NO_ACTIVATION') {
                return ['success' => true, 'response' => $body];
            }

            throw new \Exception("Server 2 cancellation failed: {$body}");
        } catch (\Throwable $e) {
            Log::error("Grizzly cancelOrder error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Mark an order as finished on Grizzly SMS.
     * Action: setStatus, status=6
     */
    public function finishOrder(string|int $orderId): array
    {
        if (empty($this->apiKey)) {
            return ['success' => true];
        }

        try {
            $response = Http::timeout(15)->get($this->baseUrl, [
                'api_key' => $this->apiKey,
                'action' => 'setStatus',
                'id' => $orderId,
                'status' => 6, // 6 = finished
            ]);

            return ['success' => true, 'response' => trim($response->body())];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Request another SMS code on the same number.
     * Action: setStatus, status=3
     */
    public function resendOrder(string|int $orderId): array
    {
        if (empty($this->apiKey)) {
            return ['success' => true];
        }

        try {
            $response = Http::timeout(15)->get($this->baseUrl, [
                'api_key' => $this->apiKey,
                'action' => 'setStatus',
                'id' => $orderId,
                'status' => 3, // 3 = request another SMS
            ]);

            $body = trim($response->body());
            if ($body === 'ACCESS_RETRY_GET' || str_contains($body, 'ACCESS')) {
                return ['success' => 1, 'message' => 'Waiting for next SMS code.'];
            }

            throw new \Exception("Server 2 resend request rejected: {$body}");
        } catch (\Throwable $e) {
            Log::error("Grizzly resendOrder error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Fallback standard country dictionary for Grizzly SMS.
     */
    protected function getDefaultCountryList(): array
    {
        return [
            ['ID' => '0', 'name' => 'Russia', 'cc' => '7'],
            ['ID' => '1', 'name' => 'Ukraine', 'cc' => '380'],
            ['ID' => '2', 'name' => 'Kazakhstan', 'cc' => '7'],
            ['ID' => '3', 'name' => 'China', 'cc' => '86'],
            ['ID' => '4', 'name' => 'Philippines', 'cc' => '63'],
            ['ID' => '5', 'name' => 'Myanmar', 'cc' => '95'],
            ['ID' => '6', 'name' => 'Indonesia', 'cc' => '62'],
            ['ID' => '7', 'name' => 'Malaysia', 'cc' => '60'],
            ['ID' => '8', 'name' => 'Kenya', 'cc' => '254'],
            ['ID' => '10', 'name' => 'Vietnam', 'cc' => '84'],
            ['ID' => '11', 'name' => 'Kyrgyzstan', 'cc' => '996'],
            ['ID' => '12', 'name' => 'USA', 'cc' => '1'],
            ['ID' => '13', 'name' => 'Israel', 'cc' => '972'],
            ['ID' => '14', 'name' => 'Hong Kong', 'cc' => '852'],
            ['ID' => '15', 'name' => 'Poland', 'cc' => '48'],
            ['ID' => '16', 'name' => 'United Kingdom', 'cc' => '44'],
            ['ID' => '18', 'name' => 'Democratic Republic of Congo', 'cc' => '243'],
            ['ID' => '22', 'name' => 'India', 'cc' => '91'],
            ['ID' => '23', 'name' => 'Cambodia', 'cc' => '855'],
            ['ID' => '24', 'name' => 'Laos', 'cc' => '856'],
            ['ID' => '29', 'name' => 'Romania', 'cc' => '40'],
            ['ID' => '31', 'name' => 'South Africa', 'cc' => '27'],
            ['ID' => '32', 'name' => 'Netherlands', 'cc' => '31'],
            ['ID' => '36', 'name' => 'Canada', 'cc' => '1'],
            ['ID' => '37', 'name' => 'Morocco', 'cc' => '212'],
            ['ID' => '38', 'name' => 'Ghana', 'cc' => '233'],
            ['ID' => '39', 'name' => 'Argentina', 'cc' => '54'],
            ['ID' => '43', 'name' => 'Germany', 'cc' => '49'],
            ['ID' => '44', 'name' => 'Lithuania', 'cc' => '370'],
            ['ID' => '47', 'name' => 'Sweden', 'cc' => '46'],
            ['ID' => '48', 'name' => 'Iraq', 'cc' => '964'],
            ['ID' => '52', 'name' => 'Thailand', 'cc' => '66'],
            ['ID' => '53', 'name' => 'Saudi Arabia', 'cc' => '966'],
            ['ID' => '56', 'name' => 'Spain', 'cc' => '34'],
            ['ID' => '62', 'name' => 'Turkey', 'cc' => '90'],
            ['ID' => '68', 'name' => 'Egypt', 'cc' => '20'],
            ['ID' => '73', 'name' => 'Nigeria', 'cc' => '234'],
            ['ID' => '78', 'name' => 'France', 'cc' => '33'],
            ['ID' => '86', 'name' => 'Italy', 'cc' => '39'],
            ['ID' => '117', 'name' => 'Portugal', 'cc' => '351'],
            ['ID' => '175', 'name' => 'Mexico', 'cc' => '52'],
            ['ID' => '187', 'name' => 'United States (Virtual)', 'cc' => '1'],
        ];
    }
}
