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

        $response = Http::timeout($timeout)->accept('*/*')->get($this->baseUrl, array_merge([
            'api_key' => $this->apiKey,
        ], $params));

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
            if ($country === self::USA_COUNTRY_ID && isset($catalogue[$service]['cost'])) {
                return $catalogue[$service];
            }
        }

        // getPrices: country => service => {cost,count,multi}
        if (isset($catalogue[$country][$service]) && is_array($catalogue[$country][$service])) {
            return $catalogue[$country][$service];
        }

        return null;
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
        if ($actualPrice <= 0) {
            // Never guess provider spend if DaisySMS omits its documented effective-price header.
            throw new \RuntimeException('Server 3 returned a number without a verifiable provider price. Order requires reconciliation.');
        }

        return [
            'order_id' => $matches[1],
            'phonenumber' => $matches[2],
            'cost' => $actualPrice,
            'provider_cost' => $actualPrice,
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
        if ($body === 'STATUS_WAIT_CODE') {
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
