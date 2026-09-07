<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GongozconceptApi
{
    protected $apiUrl;
    protected $apiKey;

    public function __construct()
    {
        $this->apiUrl = config('services.gongozconcept.api_url');
        $this->apiKey = \App\Models\Setting::get('gongozconcept_api_key', config('services.gongozconcept.api_key'));
    }

    /**
     * Helper to make API requests
     */
    protected function request($method, $endpoint, $data = [])
    {
        if (empty($this->apiKey)) {
            Log::error('Gongozconcept API key is missing.');
            return [
                'status' => false,
                'message' => 'API configuration error.',
            ];
        }

        $url = rtrim($this->apiUrl, '/') . '/' . ltrim($endpoint, '/');

        $headers = [
            'Authorization' => 'Token ' . $this->apiKey,
            'Accept' => 'application/json',
        ];

        if (strtolower($method) !== 'get') {
            $headers['Content-Type'] = 'application/json';
        }

        try {
            if (strtolower($method) === 'get') {
                $response = Http::withHeaders($headers)->get($url);
            } else {
                $response = Http::withHeaders($headers)->$method($url, $data);
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Gongozconcept API error: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Failed to connect to the VTU provider.',
            ];
        }
    }

    /**
     * Buy Airtime
     * 
     * @param int $network Network ID (e.g. 1 for MTN, 2 for GLO, etc.)
     * @param int $amount Amount to topup
     * @param string $phone Phone number
     * @param string $airtimeType VTU or Share and Sell
     */
    public function buyAirtime($network, $amount, $phone, $airtimeType = 'VTU')
    {
        $payload = [
            'network' => $network,
            'amount' => $amount,
            'mobile_number' => $phone,
            'Ported_number' => true,
            'airtime_type' => $airtimeType
        ];

        return $this->request('post', 'topup/', $payload);
    }

    /**
     * Buy Data
     * 
     * @param int $network Network ID
     * @param int $plan Data Plan ID
     * @param string $phone Phone number
     */
    public function buyData($network, $plan, $phone)
    {
        $payload = [
            'network' => $network,
            'mobile_number' => $phone,
            'plan' => $plan,
            'Ported_number' => true
        ];

        return $this->request('post', 'data/', $payload);
    }

    /**
     * Buy Electricity
     * 
     * @param int $disco Disco ID (1 for Ikeja, 2 for Eko, etc.)
     * @param int $amount Amount
     * @param string $meterNumber Meter number
     * @param string $meterType Prepaid or Postpaid
     * @param string $phone Customer phone number
     */
    public function buyElectricity($disco, $amount, $meterNumber, $meterType = 'Prepaid', $phone = '')
    {
        $payload = [
            'disco_name' => $disco,
            'amount' => $amount,
            'meter_number' => $meterNumber,
            'MeterType' => $meterType,
            'Customer_Phone' => $phone
        ];

        return $this->request('post', 'billpayment/', $payload);
    }

    /**
     * Validate Electricity Meter
     * 
     * @param string $meterNumber Meter number
     * @param int $disco Disco ID
     * @param string $meterType Prepaid or Postpaid
     */
    public function validateMeter($meterNumber, $disco, $meterType = 'Prepaid')
    {
        $url = 'validatemeter?meternumber=' . urlencode($meterNumber) . '&disconame=' . urlencode($disco) . '&mtype=' . urlencode($meterType);
        
        $response = $this->request('get', $url);
        \Illuminate\Support\Facades\Log::info("Gongoz Validate Meter Response for $meterNumber ($disco): ", (array)$response);
        return $response;
    }

    /**
     * Validate Cable Smart Card / IUC Number
     * 
     * @param string $smartCardNumber
     * @param int|string $cableName (1: GOTV, 2: DSTV, 3: STARTIME)
     */
    public function validateIUC($smartCardNumber, $cableName)
    {
        $cableMap = [
            '1' => 'GOTV',
            '2' => 'DSTV',
            '3' => 'STARTIME',
        ];

        // First attempt with the provided cableName (e.g. 1, 2, 3 or GOTV, DSTV, STARTIME)
        $url = 'validateiuc?smart_card_number=' . urlencode($smartCardNumber) . '&cablename=' . urlencode($cableName);
        $response = $this->request('get', $url);
        \Illuminate\Support\Facades\Log::info("Gongoz Validate IUC Response for $smartCardNumber ($cableName): ", (array)$response);

        // If not successful and $cableName was numeric, also try text name, or vice-versa
        $name = $response['name'] ?? ($response['Customer_Name'] ?? ($response['customer_name'] ?? null));
        if (empty($name) || strtolower($name) === 'invalid') {
            $altCable = $cableMap[(string) $cableName] ?? array_search(strtoupper($cableName), $cableMap);
            if ($altCable) {
                $altUrl = 'validateiuc?smart_card_number=' . urlencode($smartCardNumber) . '&cablename=' . urlencode($altCable);
                $altResponse = $this->request('get', $altUrl);
                \Illuminate\Support\Facades\Log::info("Gongoz Validate IUC Alt Response for $smartCardNumber ($altCable): ", (array)$altResponse);
                $altName = $altResponse['name'] ?? ($altResponse['Customer_Name'] ?? ($altResponse['customer_name'] ?? null));
                if (!empty($altName) && strtolower($altName) !== 'invalid') {
                    return $altResponse;
                }
            }
        }

        return $response;
    }

    /**
     * Buy Cable TV Subscription
     * 
     * @param int|string $cableName
     * @param int|string $cablePlan
     * @param string $smartCardNumber
     */
    public function buyCable($cableName, $cablePlan, $smartCardNumber)
    {
        $payload = [
            'cablename' => $cableName,
            'cableplan' => $cablePlan,
            'smart_card_number' => $smartCardNumber,
        ];

        return $this->request('post', 'cablesub/', $payload);
    }
}
