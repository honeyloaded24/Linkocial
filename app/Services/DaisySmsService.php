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
    public const CANADA_COUNTRY_ID = '36';
    public const SUPPORTED_COUNTRIES = [
        self::USA_COUNTRY_ID => ['name' => 'United States', 'cc' => '1'],
        self::CANADA_COUNTRY_ID => ['name' => 'Canada', 'cc' => '1'],
    ];

    /**
     * Friendly labels for common DaisySMS shortcodes. Unknown services are
     * still exposed using a readable version of the provider's own shortcode.
     */
    public const SERVICE_NAMES = [
        'whatsapp' => 'WhatsApp',
        'wa' => 'WhatsApp',
        'whatsapp_canada' => 'WhatsApp Canada',
        'telegram' => 'Telegram',
        'tg' => 'Telegram',
        'telegram_canada' => 'Telegram Canada',
        'facebook' => 'Facebook',
        'fb' => 'Facebook',
        'facebook_reset' => 'Facebook Reset',
        'google' => 'Google',
        'go' => 'Google',
        'google_chat' => 'Google Chat',
        'google_voice' => 'Google Voice',
        'google_gmail' => 'Google / YouTube / Gmail',
        'instagram' => 'Instagram',
        'ig' => 'Instagram',
        'instagram_threads' => 'Instagram / Threads',
        'twitter' => 'X / Twitter',
        'tw' => 'X / Twitter',
        'tiktok' => 'TikTok',
        'amazon' => 'Amazon',
        'am' => 'Amazon',
        'discord' => 'Discord',
        'ds' => 'Discord',
        'snapchat' => 'Snapchat',
        'sn' => 'Snapchat',
        'linkedin' => 'LinkedIn',
        'li' => 'LinkedIn',
        'uber' => 'Uber',
        'ub' => 'Uber',
        'paypal' => 'PayPal',
        'pp' => 'PayPal',
        'apple' => 'Apple',
        'ap' => 'Apple',
        'microsoft' => 'Microsoft',
        'mm' => 'Microsoft',
        'netflix' => 'Netflix',
    ];

    public function __construct()
    {
        $this->apiKey = Setting::get('daisysms_api_key');
    }

    protected function request(array $params, int $timeout = 20): Response
    {
        if (empty($this->apiKey)) {
            throw new \RuntimeException('This number service is temporarily unavailable. Please contact support.');
        }

        try {
            $response = Http::timeout($timeout)->accept('*/*')->get($this->baseUrl, array_merge([
                'api_key' => $this->apiKey,
            ], $params));
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new \App\Exceptions\ProviderTimeoutException('The number request timed out or the connection was interrupted.', 0, $e);
        }

        if (!$response->successful()) {
            // A gateway/server error does not prove whether DaisySMS processed a
            // state-changing request before returning the HTTP failure. Treat it
            // as ambiguous so callers retain the fee and block duplicate requests.
            throw new \App\Exceptions\ProviderTimeoutException(
                'The number request outcome could not be confirmed.'
            );
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


    protected function serviceName(string $serviceCode): string
    {
        $code = strtolower(trim($serviceCode));
        if (isset(self::SERVICE_NAMES[$code])) {
            return self::SERVICE_NAMES[$code];
        }

        return Str::headline(str_replace(['_', '-', '.'], ' ', $serviceCode));
    }

    /**
     * Country provider contract used by the shared Virtual Number controller.
     * DaisySMS uses SMS-Activate country IDs in its catalogue.
     */
    public function getCountries(): array
    {
        return $this->getAvailableCountries();
    }

    /**
     * Return countries that currently have at least one live DaisySMS offer.
     * The provider documents USA as 187; Canada is included only if its live
     * catalogue actually returns offers for the SMS-Activate country ID 36.
     */
    public function getAvailableCountries(): array
    {
        $countries = [];
        foreach (self::SUPPORTED_COUNTRIES as $countryId => $meta) {
            if ($this->getPricesForCountry((string) $countryId)) {
                $countries[] = [
                    'ID' => (string) $countryId,
                    'name' => $meta['name'],
                    'cc' => $meta['cc'],
                ];
            }
        }

        return $countries;
    }

    /**
     * Return the live service identifiers and offers for the configured account.
     * This is an admin-only diagnostic; it does not enable or change routing.
     */
    public function getCatalogueForCountry(string $country): array
    {
        if (!isset(self::SUPPORTED_COUNTRIES[$country])) {
            return [];
        }

        $rows = [];

        // Try both documented endpoints and parse either documented orientation,
        // regardless of which endpoint returns which shape for this account.
        foreach (['getPricesVerification', 'getPrices'] as $action) {
            try {
                $catalogue = $this->decoded($this->request(['action' => $action]));
            } catch (\Throwable $e) {
                report($e);
                continue;
            }

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

            // Shape C is a flat account-level response. DaisySMS documents
            // country 187 as USA; never reuse this flat USA data for Canada.
            if ($country === self::USA_COUNTRY_ID) foreach ($catalogue as $serviceCode => $offer) {
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

            // Merge both documented catalogue responses so services present in
            // either response orientation are not silently omitted.
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
        if (!isset(self::SUPPORTED_COUNTRIES[$country])) {
            return [];
        }

        $catalogueRows = $this->getCatalogueForCountry($country);
        $rows = [];

        foreach ($catalogueRows as $offer) {
            $price = (float) ($offer['cost_usd'] ?? 0);
            $stock = (int) ($offer['stock'] ?? 0);
            if ($price <= 0 || $stock <= 0) {
                continue;
            }

            $serviceCode = (string) $offer['service_code'];
            $rows[] = [
                'service' => $serviceCode,
                'service_name' => $this->serviceName($serviceCode),
                'price' => $price,
                'stock' => $stock,
                'multi' => (int) ($offer['multi'] ?? 0),
            ];
        }

        $nameCounts = array_count_values(array_column($rows, 'service_name'));
        foreach ($rows as &$row) {
            if (($nameCounts[$row['service_name']] ?? 0) > 1) {
                $row['service_name'] .= ' (' . $row['service'] . ')';
            }
        }
        unset($row);

        usort($rows, fn ($a, $b) => strcasecmp($a['service_name'], $b['service_name']));
        return $rows;
    }

    public function buyNumber(string $country, string $service, ?float $maxPrice = null, ?float $quotedPrice = null): array
    {
        if (!isset(self::SUPPORTED_COUNTRIES[$country]) || trim($service) === '') {
            throw new \RuntimeException('This service is not currently available for the selected country.');
        }
        $params = [
            'action' => 'getNumber',
            'service' => $service,
        ];

        // max_price is optional in DaisySMS. Linkocial's standard purchase flow
        // omits it after the customer confirms the fresh live retail quote.
        if ($maxPrice !== null && $maxPrice > 0) {
            $params['max_price'] = number_format($maxPrice, 2, '.', '');
        }

        $response = $this->request($params, 30);

        $body = trim($response->body());
        if (!preg_match('/^ACCESS_NUMBER:([^:]+):(.+)$/', $body, $matches)) {
            $messages = [
                'NO_NUMBERS' => 'No numbers are currently available for this service on Server 4.',
                'MAX_PRICE_EXCEEDED' => 'The current price exceeds the safe spending limit. Please refresh the quote.',
                'TOO_MANY_ACTIVE_RENTALS' => 'Server 4 has reached its active rental limit. Please try again after an active rental is completed.',
                'NO_MONEY' => 'Server 4 provider balance is insufficient.',
                'BAD_KEY' => 'Server 4 API authentication failed. Please contact support.',
            ];
            throw new \RuntimeException($messages[$body] ?? 'Server 4 could not complete the number purchase: '.$body);
        }

        $actualPrice = (float) ($response->header('X-Price') ?? 0);
        // DaisySMS may omit X-Price on an otherwise confirmed ACCESS_NUMBER.
        // In that case use the fresh provider catalogue quote obtained immediately
        // before purchase; do not confuse the optional max_price cap with that quote.
        $verifiedPrice = $actualPrice > 0 ? $actualPrice : (float) ($quotedPrice ?? 0);

        return [
            'order_id' => $matches[1],
            'phonenumber' => $matches[2],
            'cc' => self::SUPPORTED_COUNTRIES[$country]['cc'],
            'cost' => $verifiedPrice > 0 ? $verifiedPrice : 0.0,
            'provider_cost' => $verifiedPrice > 0 ? $verifiedPrice : 0.0,
            'price_unverified' => $verifiedPrice <= 0,
            'provider_quote_usd' => $quotedPrice ?? $maxPrice ?? 0.0,
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
        if ($body === 'NO_ACTIVATION') {
            // DaisySMS removes expired rentals from its active system. For a
            // previously confirmed local activation, NO_ACTIVATION means the
            // provider no longer has a live rental to cancel or receive SMS on.
            return ['status' => 2, 'provider_missing' => true];
        }
        if ($body === 'RATE_LIMIT_EXCEEDED') {
            throw new \RuntimeException('The number status service is temporarily busy. Please wait briefly and try again.');
        }

        throw new \RuntimeException('The number status could not be verified. Please wait briefly and try again.');
    }

    public function cancelOrder(string $activationId): array
    {
        $response = $this->request([
            'action' => 'setStatus',
            'id' => $activationId,
            'status' => 8,
        ]);
        $body = trim($response->body());

        if ($body === 'ACCESS_READY') {
            // DaisySMS documents ACCESS_READY as ambiguous: the rental is either
            // missing or has already received a code. Only now make a follow-up
            // status request (not a preflight) to distinguish those cases.
            $status = $this->checkOrder($activationId);

            if ((int) ($status['status'] ?? -1) === 2 || (int) ($status['status'] ?? -1) === 6) {
                return [
                    'success' => true,
                    'status' => (int) $status['status'] === 2 ? 'expired' : 'canceled',
                    'provider_missing' => (int) $status['status'] === 2,
                ];
            }

            if ((int) ($status['status'] ?? -1) === 3) {
                throw new \RuntimeException('This number has already received an SMS and cannot be refunded.');
            }

            throw new \RuntimeException('The number is still active with the provider. Cancellation was not confirmed and no refund was issued. Please retry shortly.');
        }

        if ($body !== 'ACCESS_CANCEL') {
            // RATE_LIMIT_EXCEEDED is retryable; no refund is authorized.
            $message = match ($body) {
                'RATE_LIMIT_EXCEEDED' => 'The cancellation service is temporarily busy. No refund was issued. Please wait briefly and try again.',
                default => 'Cancellation could not be confirmed. No refund was issued. Please try again shortly.',
            };
            throw new \RuntimeException($message);
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
            throw new \InvalidArgumentException('A valid number activation is required.');
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
            'BAD_ID' => 'Another SMS is not available for this number.',
            'NO_BALANCE' => 'Server 4 provider balance is insufficient for another SMS activation.',
        ];

        if (isset($messages[$body])) {
            // These documented responses explicitly reject the request, so the
            // controller may safely reverse the customer's fee.
            throw new \RuntimeException($messages[$body]);
        }

        // Unknown provider responses are not proof of rejection. DaisySMS may
        // have created the extra activation despite an unfamiliar response.
        throw new \App\Exceptions\ProviderTimeoutException(
            'The additional SMS request outcome could not be confirmed.'
        );
    }

    public function finishOrder(string $activationId): array
    {
        $response = $this->request([
            'action' => 'setStatus',
            'id' => $activationId,
            'status' => 6,
        ]);
        if (trim($response->body()) !== 'ACCESS_ACTIVATION') {
            throw new \RuntimeException('The number could not be marked as finished.');
        }

        return ['success' => true];
    }
}
