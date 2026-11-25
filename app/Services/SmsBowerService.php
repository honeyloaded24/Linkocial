<?php

namespace App\Services;

use App\Exceptions\ProviderTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Str;

class SmsBowerService
{
    protected string $baseUrl = 'https://smsbower.page/stubs/handler_api.php';
    protected ?string $apiKey;

    public function __construct()
    {
        $this->apiKey = \App\Models\Setting::get('smsbower_api_key');
    }

    protected function request(array $params, int $timeout = 15): \Illuminate\Http\Client\Response
    {
        if (empty($this->apiKey)) {
            throw new \RuntimeException('Server 3 API key is not configured.');
        }

        return Http::timeout($timeout)->get($this->baseUrl, array_merge([
            'api_key' => $this->apiKey,
        ], $params));
    }

    public function getCountries(): array
    {
        $response = $this->request(['action' => 'getCountries'], 20);
        if (!$response->successful()) {
            throw new \RuntimeException('Server 3 could not retrieve countries.');
        }

        $data = $response->json();
        if (!is_array($data)) {
            throw new \RuntimeException('Server 3 returned an invalid countries response.');
        }

        $countries = [];
        foreach ($data as $key => $row) {
            if (!is_array($row)) {
                continue;
            }
            $id = $row['id'] ?? $row['ID'] ?? (is_numeric($key) ? $key : null);
            $name = $row['eng'] ?? $row['name'] ?? null;
            if ($id === null || !$name) {
                continue;
            }
            $countries[] = [
                'ID' => (string) $id,
                'name' => (string) $name,
                'cc' => (string) ($row['prefix'] ?? $row['code'] ?? ''),
            ];
        }

        return $countries;
    }

    public function getServices(): array
    {
        $response = $this->request(['action' => 'getServicesList'], 20);
        if (!$response->successful()) {
            throw new \RuntimeException('Server 3 could not retrieve services.');
        }

        $data = $response->json();
        $rows = $data['services'] ?? $data;
        if (!is_array($rows)) {
            throw new \RuntimeException('Server 3 returned an invalid services response.');
        }

        $services = [];
        foreach ($rows as $key => $row) {
            if (is_array($row)) {
                $code = $row['code'] ?? $row['id'] ?? null;
                $name = $row['name'] ?? null;
            } else {
                $code = is_string($key) ? $key : null;
                $name = is_scalar($row) ? (string) $row : null;
            }
            if ($code !== null && $name !== null && trim((string) $name) !== '') {
                $services[] = ['code' => (string) $code, 'name' => (string) $name];
            }
        }

        return $services;
    }

    protected function countryKey(string $name): string
    {
        $key = Str::lower(preg_replace('/[^a-z0-9]/i', '', $name) ?? '');
        $aliases = [
            'unitedstates' => 'usa',
            'unitedstatesofamerica' => 'usa',
            'us' => 'usa',
            'unitedkingdom' => 'uk',
            'greatbritain' => 'uk',
            'uk' => 'uk',
            'uae' => 'unitedarabemirates',
            'southkorea' => 'korea',
            'republicofkorea' => 'korea',
            'northkorea' => 'koreademocraticpeoplesrepublic',
        ];

        return $aliases[$key] ?? $key;
    }

    protected function goldPartnersFromResponse(array $data, string $countryName): array
    {
        $target = $this->countryKey($countryName);
        foreach ($data as $countryKey => $partners) {
            if ($this->countryKey((string) $countryKey) !== $target || !is_array($partners)) continue;
            $eligible = [];
            foreach ($partners as $partnerId => $details) {
                if (!is_array($details)) continue;
                $price = (float) ($details['price'] ?? 0);
                $count = (int) ($details['count'] ?? 0);
                if ($price > 0 && (string) $partnerId !== '') {
                    $eligible[] = ['id' => (string) ($details['provider_id'] ?? $partnerId), 'price' => $price, 'count' => $count];
                }
            }
            usort($eligible, fn ($a, $b) => $a['price'] <=> $b['price']);
            return $eligible;
        }
        return [];
    }

    protected function findGoldPartners(string $serviceCode, string $countryName): array
    {
        $data = Cache::remember('smsbower_gold_top_' . md5($serviceCode), now()->addMinutes(15), function () use ($serviceCode) {
            $response = $this->request(['action' => 'getTopCountriesByService', 'service' => $serviceCode], 15);
            if (!$response->successful()) throw new \RuntimeException('Server 3 could not retrieve Gold supplier availability.');
            $json = $response->json();
            if (!is_array($json)) throw new \RuntimeException('Server 3 returned an invalid Gold supplier response.');
            return $json;
        });
        return $this->goldPartnersFromResponse($data, $countryName);
    }

    /**
     * Resolve live price and stock from getPricesV3, intersected strictly with
     * the Gold-ranked partner IDs returned for this exact service/country.
     */
    /**
     * Keep only the middle Gold price bands. The cheapest and most expensive
     * distinct Gold prices are excluded; all suppliers sharing a retained
     * middle price remain eligible. Fewer than three distinct price bands
     * means there is no defensible middle-priced supplier.
     */
    protected function middlePricedOffers(array $offers): array
    {
        $prices = array_values(array_unique(array_map(
            fn ($offer) => (float) ($offer['price'] ?? 0),
            array_filter($offers, fn ($offer) => is_array($offer) && (float) ($offer['price'] ?? 0) > 0)
        )));
        sort($prices, SORT_NUMERIC);

        if (count($prices) < 3) {
            return [];
        }

        $lowest = $prices[0];
        $highest = $prices[count($prices) - 1];
        $middle = array_values(array_filter($offers, function ($offer) use ($lowest, $highest) {
            $price = (float) ($offer['price'] ?? 0);
            return $price > $lowest && $price < $highest;
        }));

        usort($middle, fn ($a, $b) => $a['price'] <=> $b['price']);
        return $middle;
    }

    protected function supplierMaxPrice(float $providerPrice, ?float $maxPrice): float
    {
        if ($providerPrice <= 0) {
            return 0.0;
        }

        return $maxPrice !== null && $maxPrice > 0
            ? min($maxPrice, $providerPrice)
            : $providerPrice;
    }

    public function getLiveGoldOffer(string|int $country, string $service): array
    {
        $countries = $this->getCountries();
        $countryRow = collect($countries)->first(fn ($row) => (string) $row['ID'] === (string) $country);
        if (!$countryRow) {
            return [];
        }

        return $this->resolveLiveGoldOffer($country, $service, $countryRow);
    }

    /**
     * Use one source of truth for both the customer catalogue and live quote:
     * Gold membership from getTopCountriesByService, intersected with live
     * getPricesV3 stock/prices, then filtered to the middle Gold price bands.
     */
    protected function customerQuotePrice(array $offers): float
    {
        if (empty($offers)) {
            return 0.0;
        }

        // The purchase routine attempts retained middle-band suppliers in ascending
        // price order. Quote the first/cheapest eligible band, not the most expensive
        // fallback band, so customers are not charged for a supplier that was not used.
        return (float) min(array_column($offers, 'price'));
    }

    protected function resolveLiveGoldOffer(string|int $country, string $service, array $countryRow): array
    {
        $goldPartners = $this->findGoldPartners($service, $countryRow['name']);
        if (empty($goldPartners)) {
            return [];
        }

        $goldIds = array_map('strval', array_column($goldPartners, 'id'));
        $response = $this->request([
            'action' => 'getPricesV3',
            'service' => $service,
            'country' => $country,
        ], 15);

        if (!$response->successful()) {
            throw new \RuntimeException('Server 3 could not verify live Gold supplier stock and pricing.');
        }

        $data = $response->json();
        if (!is_array($data)) {
            throw new \RuntimeException('Server 3 returned invalid live Gold pricing data.');
        }

        $countryData = $data[(string) $country] ?? $data[(int) $country] ?? (count($data) === 1 ? reset($data) : $data);
        $serviceData = is_array($countryData)
            ? ($countryData[$service] ?? (count($countryData) === 1 ? reset($countryData) : $countryData))
            : [];
        if (!is_array($serviceData)) {
            return [];
        }

        $offers = [];
        foreach ($serviceData as $providerKey => $details) {
            if (!is_array($details)) {
                continue;
            }

            $providerId = (string) ($details['provider_id'] ?? $providerKey);
            $price = (float) ($details['price'] ?? $details['cost'] ?? 0);
            $stock = (int) ($details['count'] ?? 0);

            if (!in_array($providerId, $goldIds, true) || $price <= 0 || $stock <= 0) {
                continue;
            }

            $offers[] = [
                'id' => $providerId,
                'price' => $price,
                'stock' => $stock,
            ];
        }

        $offers = $this->middlePricedOffers($offers);
        if (empty($offers)) {
            return [];
        }

        // Quote the cheapest retained middle band because buyNumber tries suppliers
        // in ascending order. More expensive fallbacks remain bounded by the
        // controller's provider-spend ceiling and cannot silently overcharge users.
        return [
            'price' => $this->customerQuotePrice($offers),
            'stock' => array_sum(array_column($offers, 'stock')),
            'providers' => $offers,
            'provider_ids' => array_values(array_unique(array_column($offers, 'id'))),
        ];
    }

    public function getPricesForCountry(string|int $country): array
    {
        $cacheKey = 'smsbower_gold_middle_catalogue_v4_' . $country;
        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($country) {
            $countries = $this->getCountries();
            $countryRow = collect($countries)->first(fn ($row) => (string) $row['ID'] === (string) $country);
            if (!$countryRow) return [];

            $services = $this->getServices();
            if (empty($services)) throw new \RuntimeException('Server 3 returned no services from its service catalogue.');

            // The documented top-countries response already contains Gold supplier prices and stock.
            // Use it for catalogue discovery; getPricesV3 remains the live quote/purchase revalidation source.
            // Fetch uncached service responses in bounded concurrent batches instead of sequentially.
            $topData = [];
            $missing = [];
            foreach ($services as $service) {
                $code = (string) ($service['code'] ?? '');
                if ($code === '') continue;
                $cached = Cache::get('smsbower_gold_top_' . md5($code));
                if (is_array($cached)) $topData[$code] = $cached;
                else $missing[] = $code;
            }

            $successfulLookups = count($topData);
            foreach (array_chunk(array_values(array_unique($missing)), 12) as $batch) {
                try {
                    $responses = Http::pool(function (Pool $pool) use ($batch) {
                        $pending = [];
                        foreach ($batch as $code) {
                            $pending[$code] = $pool->as($code)->connectTimeout(5)->timeout(12)->get($this->baseUrl, [
                                'api_key' => $this->apiKey,
                                'action' => 'getTopCountriesByService',
                                'service' => $code,
                            ]);
                        }
                        return $pending;
                    });

                    foreach ($batch as $code) {
                        // Each request is explicitly keyed by its service code
                        // through Pool::as(), so read the matching keyed response.
                        $response = $responses[$code] ?? null;
                        if (!$response || !$response->successful()) {
                            Log::warning('Server 3 Gold catalogue request failed.', [
                                'service' => $code, 'country' => $country,
                                'status' => $response ? $response->status() : null,
                            ]);
                            continue;
                        }
                        $json = $response->json();
                        if (!is_array($json)) {
                            Log::warning('Server 3 returned invalid Gold catalogue JSON.', ['service' => $code, 'country' => $country]);
                            continue;
                        }
                        $topData[$code] = $json;
                        Cache::put('smsbower_gold_top_' . md5($code), $json, now()->addMinutes(15));
                        $successfulLookups++;
                    }
                } catch (\Throwable $e) {
                    Log::warning('Server 3 concurrent Gold catalogue batch failed.', [
                        'country' => $country, 'services' => $batch, 'error' => $e->getMessage(),
                    ]);
                }
            }

            if ($successfulLookups === 0) {
                throw new \RuntimeException('Server 3 could not retrieve Gold supplier catalogue data. Please try again shortly.');
            }

            $items = [];
            foreach ($services as $service) {
                $code = (string) ($service['code'] ?? '');
                $name = trim((string) ($service['name'] ?? ''));
                if ($code === '' || $name === '' || !isset($topData[$code])) continue;
                $goldOffers = $this->goldPartnersFromResponse($topData[$code], $countryRow['name']);
                $inStockGoldOffers = array_values(array_filter(
                    $goldOffers,
                    fn ($offer) => (int) ($offer['count'] ?? 0) > 0
                ));
                $middleOffers = $this->middlePricedOffers($inStockGoldOffers);
                if (empty($middleOffers)) continue;

                $items[] = [
                    'service' => $code,
                    'service_name' => $name,
                    'price' => $this->customerQuotePrice($middleOffers),
                    'count' => (int) array_sum(array_column($middleOffers, 'count')),
                    'gold_provider_ids' => array_values(array_unique(array_column($middleOffers, 'id'))),
                ];
            }
            return $items;
        });
    }

    public function buyNumber(string|int $country, string $service, ?float $maxPrice = null): array
    {
        // Refresh Gold membership and current stock immediately before purchase.
        Cache::forget('smsbower_gold_top_' . md5($service));
        $liveOffer = $this->getLiveGoldOffer($country, $service);
        $offers = $liveOffer['providers'] ?? [];

        if (empty($offers)) {
            throw new \RuntimeException('No verified Gold suppliers currently have stock for this service and country.');
        }

        $attempted = [];
        foreach ($offers as $offer) {
            $providerId = (string) ($offer['id'] ?? '');
            $providerPrice = (float) ($offer['price'] ?? 0);
            if ($providerId === '' || $providerPrice <= 0) {
                continue;
            }
            if ($maxPrice !== null && $maxPrice > 0 && $providerPrice > $maxPrice) {
                continue;
            }

            $attempted[] = $providerId;
            $params = [
                'action' => 'getNumberV2',
                'service' => $service,
                'country' => $country,
                // One ID per attempt guarantees we try the cheapest live Gold supplier first.
                'providerIds' => $providerId,
            ];
            $supplierMaxPrice = $this->supplierMaxPrice($providerPrice, $maxPrice);
            $params['maxPrice'] = number_format($supplierMaxPrice, 4, '.', '');

            try {
                $response = $this->request($params, 20);
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                // A timeout is ambiguous. Never try another supplier after a possibly successful purchase.
                throw new ProviderTimeoutException('Server 3 purchase timed out; activation outcome is unknown.', 0, $e);
            }

            if (!$response->successful()) {
                if ($response->serverError()) {
                    throw new ProviderTimeoutException('Server 3 returned an ambiguous server error during purchase; activation outcome is unknown.');
                }
                // A definite client-side rejection did not create an activation; try the next Gold ID.
                continue;
            }

            $body = trim($response->body());
            $data = $response->json();

            if (is_array($data) && !empty($data['activationId'])) {
                return [
                    'order_id' => (string) $data['activationId'],
                    'phonenumber' => (string) ($data['phoneNumber'] ?? ''),
                    'cc' => (string) ($data['countryCode'] ?? ''),
                    'cost' => (float) ($data['activationCost'] ?? 0),
                    'time_left' => 1200,
                    'provider_id' => $data['activationOperator'] ?? $providerId,
                    'requested_provider_id' => $providerId,
                    'provider_quote_usd' => $providerPrice,
                    'provider_returned_operator' => isset($data['activationOperator']) ? (string) $data['activationOperator'] : null,
                ];
            }

            if (preg_match('/^ACCESS_NUMBER:([^:]+):(.+)$/', $body, $matches)) {
                return [
                    'order_id' => $matches[1],
                    'phonenumber' => $matches[2],
                    'cc' => '',
                    'cost' => $providerPrice,
                    'time_left' => 1200,
                    'provider_id' => $providerId,
                    'requested_provider_id' => $providerId,
                    'provider_quote_usd' => $providerPrice,
                    'provider_returned_operator' => null,
                ];
            }

            $error = (string) ($data['message'] ?? ($body !== '' ? $body : ''));
            $knownNoPurchaseErrors = [
                'BAD_KEY', 'BAD_ACTION', 'BAD_SERVICE', 'BAD_COUNTRY',
                'NO_NUMBERS', 'NO_BALANCE', 'MAX_PRICE_EXCEEDED',
                'NO_ACTIVATION', 'EARLY_CANCEL_DENIED',
            ];
            $definiteNoPurchase = false;
            foreach ($knownNoPurchaseErrors as $knownError) {
                if (stripos($error, $knownError) !== false) {
                    $definiteNoPurchase = true;
                    break;
                }
            }

            if ($definiteNoPurchase) {
                // Only a documented/recognized rejection permits trying another Gold supplier.
                continue;
            }

            // Unknown provider responses are ambiguous: retain the debit and reconcile.
            throw new ProviderTimeoutException(
                'Server 3 returned an unrecognized purchase response; activation outcome is unknown.',
                0,
                new \RuntimeException(substr($error, 0, 250))
            );
        }

        if (empty($attempted)) {
            throw new \RuntimeException('No Gold supplier price is within the confirmed purchase limit.');
        }

        throw new \RuntimeException('No verified Gold supplier could fulfil this request at the confirmed price.');
    }

    public function checkOrder(string|int $orderId): array
    {
        $response = $this->request([
            'action' => 'getStatus',
            'id' => $orderId,
        ]);
        if (!$response->successful()) {
            throw new \RuntimeException('Server 3 status check failed.');
        }

        $body = trim($response->body());
        if (str_starts_with($body, 'STATUS_OK:')) {
            $code = trim(substr($body, strlen('STATUS_OK:')));
            return ['status' => 3, 'sms' => $code, 'code' => $code, 'full_sms' => $code];
        }
        if (str_starts_with($body, 'STATUS_WAIT_RETRY')) {
            $lastCode = trim(substr($body, strlen('STATUS_WAIT_RETRY')));
            $lastCode = ltrim($lastCode, ':');
            if ($lastCode !== '') {
                return ['status' => 3, 'sms' => $lastCode, 'code' => $lastCode, 'full_sms' => $lastCode];
            }
            return ['status' => 1, 'sms' => '', 'code' => ''];
        }
        if (str_starts_with($body, 'STATUS_WAIT_CODE')) {
            return ['status' => 1, 'sms' => '', 'code' => ''];
        }
        if (str_starts_with($body, 'STATUS_CANCEL')) {
            return ['status' => 6, 'sms' => '', 'code' => ''];
        }

        throw new \RuntimeException('Server 3 returned an unrecognized activation status.');
    }

    protected function setStatus(string|int $orderId, int $status): string
    {
        $response = $this->request([
            'action' => 'setStatus',
            'id' => $orderId,
            'status' => $status,
        ]);
        if (!$response->successful()) {
            throw new \RuntimeException('Server 3 activation status request failed.');
        }

        $body = trim($response->body());
        if (str_starts_with($body, 'ACCESS_')) {
            return $body;
        }

        throw new \RuntimeException($body !== '' ? substr($body, 0, 200) : 'Server 3 did not confirm the requested activation status.');
    }

    public function cancelOrder(string|int $orderId): array
    {
        // Never cancel or refund unless the provider confirms the activation is still waiting for SMS.
        $current = $this->checkOrder($orderId);
        if ((int) ($current['status'] ?? -1) !== 1) {
            throw new \RuntimeException('Server 3 cannot confirm that this number is still waiting for an SMS. No cancellation or refund was issued.');
        }

        $result = $this->setStatus($orderId, 8);
        if ($result !== 'ACCESS_CANCEL') {
            throw new \RuntimeException('Server 3 did not confirm cancellation. No refund was issued.');
        }
        return ['success' => true, 'response' => $result];
    }

    public function resendOrder(string|int $orderId): array
    {
        $result = $this->setStatus($orderId, 3);
        if ($result !== 'ACCESS_RETRY_GET') {
            throw new \RuntimeException('Server 3 did not confirm the SMS resend request.');
        }
        return ['success' => 1, 'message' => 'Waiting for the next SMS code.'];
    }

    public function finishOrder(string|int $orderId): array
    {
        $result = $this->setStatus($orderId, 6);
        return ['success' => $result === 'ACCESS_ACTIVATION', 'response' => $result];
    }
}
