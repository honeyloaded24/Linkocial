<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class DaisySmsService
{
    protected string $baseUrl = 'https://daisysms.io/stubs/handler_api.php';
    protected ?string $apiKey;

    public const USA_COUNTRY_ID = '187';

    public const ROUTED_SERVICES = [
        'whatsapp' => 'WhatsApp',
        'telegram' => 'Telegram',
        'whatsapp_canada' => 'WhatsApp Canada',
        'telegram_canada' => 'Telegram Canada',
    ];

    public function __construct()
    {
        $this->apiKey = Setting::get('daisysms_api_key');
    }

    protected function request(array $params, int $timeout = 20): Response
    {
        if (empty($this->apiKey)) {
            throw new \RuntimeException('DaisySMS API key is not configured for Server 3.');
        }

        try {
            $response = Http::timeout($timeout)->accept('*/*')->get($this->baseUrl, array_merge([
                'api_key' => $this->apiKey,
            ], $params));
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new \App\Exceptions\ProviderTimeoutException('DaisySMS request timed out or the connection was interrupted.', 0, $e);
        }

        if (!$response->successful()) {
            throw new \RuntimeException('DaisySMS request failed with HTTP '.$response->status().'.');
        }

        return $response;
    }

    protected function decoded(Response $response): array
    {
        $data = $response->json();
        return is_array($data) ? $data : [];
    }

    protected function serviceData(array $catalogue, string $service, string $country): ?array
    {
        // getPricesVerification: service => country => {cost,count,multi}
        if (isset($catalogue[$service]) && is_array($catalogue[$service])) {
            $row = $catalogue[$service][$country] ?? null;
            if (is_array($row) && isset($row['cost'])) {
                return $row;
            }

            // Some account responses flatten the country dimension. DaisySMS documents
            // USA as the SMS-Activate country ID 187; only accept flat data for that ID.
            if ($country === self::USA_COUNTRY_ID && (isset($catalogue[$service]['cost']) || isset($catalogue[$service]['count']))) {
                return $catalogue[$service];
            }
        }

        // getPrices: country => service => {cost,count,multi}
        if (isset($catalogue[$country][$service]) && is_array($catalogue[$country][$service])) {
            return $catalogue[$country][$service];
        }

        return null;
    }


    /**
     * Return the live service identifiers and offers for the configured account.
     * This is an admin-only diagnostic; it does not enable or change routing.
     */
    public function getCatalogueForCountry(string $country): array
    {
        if ($country !== self::USA_COUNTRY_ID) {
            return [];
        }

        $rows = [];

        // Try both documented endpoints and parse either documented orientation,
        // regardless of which endpoint returns which shape for this account.
        foreach (['getPricesVerification', 'getPrices'] as $action) {
            $catalogue = $this->decoded($this->request(['action' => $action]));

            // Some compatible API responses wrap the catalogue in a data object.
            if (isset($catalogue['data']) && is_array($catalogue['data'])) {
                $catalogue = $catalogue['data'];
            }

            // Shape A: service => country => offer.
            foreach ($catalogue as $serviceCode => $countries) {
                if (!is_array($countries) || !isset($countries[$country]) || !is_array($countries[$country])) {
                    continue;
                }

                $offer = $countries[$country];
                if (!array_key_exists('cost', $offer) && !array_key_exists('count', $offer)) {
                    continue;
                }

                $rows[(string) $serviceCode] = [
                    'service_code' => (string) $serviceCode,
                    'country_id' => $country,
                    'cost_usd' => isset($offer['cost']) ? (float) $offer['cost'] : null,
                    'stock' => isset($offer['count']) ? (int) $offer['count'] : null,
                    'multi' => isset($offer['multi']) ? (int) $offer['multi'] : null,
                ];
            }

            // Shape C: account/country-scoped service => offer.
            // This is the actual shape returned by the configured account: each
            // top-level service code maps directly to cost/count/multi.
            foreach ($catalogue as $serviceCode => $offer) {
                if (!is_array($offer) || (!array_key_exists('cost', $offer) && !array_key_exists('count', $offer))) {
                    continue;
                }

                $rows[(string) $serviceCode] = [
                    'service_code' => (string) $serviceCode,
                    'country_id' => $country,
                    'cost_usd' => isset($offer['cost']) ? (float) $offer['cost'] : null,
                    'stock' => isset($offer['count']) ? (int) $offer['count'] : null,
                    'multi' => isset($offer['multi']) ? (int) $offer['multi'] : null,
                ];
            }

            // Shape B: country => service => offer.
            if (isset($catalogue[$country]) && is_array($catalogue[$country])) {
                foreach ($catalogue[$country] as $serviceCode => $offer) {
                    if (!is_array($offer) || (!array_key_exists('cost', $offer) && !array_key_exists('count', $offer))) {
                        continue;
                    }

                    $rows[(string) $serviceCode] = [
                        'service_code' => (string) $serviceCode,
                        'country_id' => $country,
                        'cost_usd' => isset($offer['cost']) ? (float) $offer['cost'] : null,
                        'stock' => isset($offer['count']) ? (int) $offer['count'] : null,
                        'multi' => isset($offer['multi']) ? (int) $offer['multi'] : null,
                    ];
                }
            }

            if ($rows) {
                break;
            }
        }

        ksort($rows);
        return array_values($rows);
    }


    /**
     * Safe response-shape diagnostics for the two documented catalogue actions.
     * Never includes request URLs, credentials, or full provider payloads.
     */
    public function getCatalogueDiagnostics(): array
    {
        $diagnostics = [];

        foreach (['getPricesVerification', 'getPrices'] as $action) {
            $response = $this->request(['action' => $action]);
            $data = $response->json();
            $decoded = is_array($data) ? $data : [];
            $root = isset($decoded['data']) && is_array($decoded['data']) ? $decoded['data'] : $decoded;
            $firstKey = array_key_first($root);
            $firstValue = $firstKey !== null ? $root[$firstKey] : null;

            $diagnostics[] = [
                'action' => $action,
                'http_status' => $response->status(),
                'content_type' => $response->header('Content-Type'),
                'json_response' => is_array($data),
                'top_level_count' => count($root),
                'top_level_keys_sample' => array_slice(array_map('strval', array_keys($root)), 0, 20),
                'first_entry_key' => $firstKey !== null ? (string) $firstKey : null,
                'first_entry_type' => gettype($firstValue),
                'first_entry_keys' => is_array($firstValue) ? array_slice(array_map('strval', array_keys($firstValue)), 0, 20) : [],
                'body_preview' => is_array($data) ? null : mb_substr(trim($response->body()), 0, 300),
            ];
        }

        return $diagnostics;
    }

    public function getPricesForCountry(string $country): array
    {
        if ($country !== self::USA_COUNTRY_ID) {
            return [];
        }

        $response = $this->request(['action' => 'getPricesVerification']);
        $catalogue = $this->decoded($response);
        if (!$catalogue) {
            $catalogue = $this->decoded($this->request(['action' => 'getPrices']));
        }

        $rows = [];
        foreach (self::ROUTED_SERVICES as $code => $name) {
            $offer = $this->serviceData($catalogue, $code, $country);
            if (!$offer) {
                continue;
            }

            $price = (float) ($offer['cost'] ?? 0);
            $stock = (int) ($offer['count'] ?? 0);
            if ($price <= 0 || $stock <= 0) {
                continue;
            }

            $rows[] = [
                'service' => $code,
                'service_name' => $name,
                'price' => $price,
                'stock' => $stock,
                'multi' => (int) ($offer['multi'] ?? 0),
            ];
        }

        return $rows;
    }

    public function buyNumber(string $country, string $service, float $maxPrice): array
    {
        if ($country !== self::USA_COUNTRY_ID || !isset(self::ROUTED_SERVICES[$service])) {
            throw new \RuntimeException('This DaisySMS service is not enabled for the selected Server 3 country.');
        }
        if ($maxPrice <= 0) {
            throw new \RuntimeException('The safe provider spending limit is too low to purchase this number.');
        }

        $response = $this->request([
            'action' => 'getNumber',
            'service' => $service,
            'max_price' => number_format($maxPrice, 2, '.', ''),
        ], 30);

        $body = trim($response->body());
        if (!preg_match('/^ACCESS_NUMBER:([^:]+):(.+)$/', $body, $matches)) {
            $messages = [
                'NO_NUMBERS' => 'No numbers are currently available for this service on Server 3.',
                'MAX_PRICE_EXCEEDED' => 'DaisySMS price exceeded the safe spending limit. Please request a fresh quote.',
                'TOO_MANY_ACTIVE_RENTALS' => 'Server 3 has reached its active rental limit. Please try again after an active rental is completed.',
                'NO_MONEY' => 'Server 3 provider balance is insufficient.',
                'BAD_KEY' => 'Server 3 API authentication failed. Please contact support.',
            ];
            throw new \RuntimeException($messages[$body] ?? 'Server 3 could not complete the number purchase: '.$body);
        }

        $actualPrice = (float) ($response->header('X-Price') ?? 0);

        return [
            'order_id' => $matches[1],
            'phonenumber' => $matches[2],
            // ACCESS_NUMBER confirms a provider rental. Missing X-Price must be
            // reconciled, not thrown as a failed purchase that could be refunded.
            'cost' => $actualPrice > 0 ? $actualPrice : 0.0,
            'provider_cost' => $actualPrice > 0 ? $actualPrice : 0.0,
            'price_unverified' => $actualPrice <= 0,
            'provider_quote_usd' => $maxPrice,
            'provider_supplier_id' => null,
            'provider_returned_operator' => null,
        ];
    }

    public function checkOrder(string $activationId): array
    {
        $response = $this->request([
            'action' => 'getStatus',
            'id' => $activationId,
            'text' => 1,
        ]);
        $body = trim($response->body());

        if (str_starts_with($body, 'STATUS_OK:')) {
            $code = trim(substr($body, strlen('STATUS_OK:')));
            return [
                'status' => 3,
                'sms' => $code,
                'sms_code' => $code,
                'full_sms' => $response->header('X-Text') ?: $code,
            ];
        }
        if (in_array($body, ['STATUS_WAIT_CODE', 'STATUS_WAITING'], true)) {
            return ['status' => 1];
        }
        if ($body === 'STATUS_CANCEL') {
            return ['status' => 6];
        }

        throw new \RuntimeException('DaisySMS status could not be verified: '.$body);
    }

    public function cancelOrder(string $activationId): array
    {
        $response = $this->request([
            'action' => 'setStatus',
            'id' => $activationId,
            'status' => 8,
        ]);
        $body = trim($response->body());

        if ($body !== 'ACCESS_CANCEL') {
            // ACCESS_READY explicitly means cancellation failed / code already received.
            throw new \RuntimeException($body === 'ACCESS_READY'
                ? 'DaisySMS reports that this rental is already ready or has received a code. No refund was issued.'
                : 'DaisySMS did not confirm cancellation. No refund was issued.');
        }

        return ['success' => true, 'status' => 'canceled'];
    }

    /**
     * Request an additional SMS on an existing DaisySMS number.
     *
     * DaisySMS may return ASLEEP with a Unix readiness timestamp while the
     * number is being switched, or ACCESS_NUMBER when it is ready immediately.
     */
    public function requestExtraActivation(string $activationId): array
    {
        if ($activationId === '') {
            throw new \InvalidArgumentException('A valid DaisySMS activation is required.');
        }

        $response = $this->request([
            'action' => 'getExtraActivation',
            'activationId' => $activationId,
        ], 30);

        $body = trim($response->body());

        if (preg_match('/^ACCESS_NUMBER:([^:]+):(.+)$/', $body, $matches)) {
            return [
                'status' => 'ready',
                'activation_id' => $matches[1],
                'phone_number' => $matches[2],
                'ready_at' => null,
            ];
        }

        if (preg_match('/^ASLEEP:([^:]+):([^:]+):(\\d+)$/', $body, $matches)) {
            return [
                'status' => 'waiting',
                'activation_id' => $matches[1],
                'phone_number' => $matches[2],
                'ready_at' => (int) $matches[3],
            ];
        }

        $messages = [
            'BAD_ID' => 'DaisySMS cannot provide another activation for this number. It may no longer be available.',
            'NO_BALANCE' => 'Server 3 provider balance is insufficient for another SMS activation.',
        ];

        throw new \RuntimeException($messages[$body] ?? 'Server 3 could not request another SMS activation: '.$body);
    }

    public function finishOrder(string $activationId): array
    {
        $response = $this->request([
            'action' => 'setStatus',
            'id' => $activationId,
            'status' => 6,
        ]);
        if (trim($response->body()) !== 'ACCESS_ACTIVATION') {
            throw new \RuntimeException('DaisySMS could not mark this rental as finished.');
        }

        return ['success' => true];
    }
}
