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
        $this->apiKey = env('SMSPOOL_API_KEY');
    }

    /**
     * Get the balance of the SMSPool account.
     * Endpoint: GET /request/balance
     */
    public function getBalance(): array
    {
        $response = Http::get("{$this->baseUrl}/request/balance", [
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
        $response = Http::get("{$this->baseUrl}/country/retrieve_all");

        if ($response->failed()) {
            Log::error('SMSPool getCountries failed', ['response' => $response->body()]);
            $this->handleFailedResponse($response, 'Failed to get countries from SMSPool');
        }

        return $response->json();
    }

    /**
     * Get pricing for services in a specific country.
     * Endpoint: POST /request/pricing
     * WARNING: We must specify the country parameter to avoid fetching the entire database
     * which exceeds the PHP memory limit.
     */
    public function getPricesForCountry(string|int $country): array
    {
        $response = Http::asForm()->post("{$this->baseUrl}/request/pricing", [
            'country' => $country,
            'key' => $this->apiKey,
        ]);

        if ($response->failed()) {
            Log::error('SMSPool getPricesForCountry failed', ['country' => $country, 'response' => $response->body()]);
            $this->handleFailedResponse($response, 'Failed to get pricing from SMSPool');
        }

        return $response->json();
    }

    /**
     * Buy a virtual phone number.
     * Endpoint: POST /purchase/sms
     */
    public function buyNumber(string|int $country, string|int $service): array
    {
        $response = Http::asForm()->post("{$this->baseUrl}/purchase/sms", [
            'country' => $country,
            'service' => $service,
            'key' => $this->apiKey,
        ]);

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
        $response = Http::asForm()->post("{$this->baseUrl}/sms/check", [
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
        $response = Http::asForm()->post("{$this->baseUrl}/sms/cancel", [
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
