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
     * Map Frontend Disco Names to Gongoz Disco IDs and Names
     */
    protected static array $discoMap = [
        '1' => 'Ikeja Electric',
        '2' => 'Eko Electric',
        '3' => 'Abuja Electric',
        '4' => 'Kano Electric',
        '5' => 'Enugu Electric',
        '6' => 'Port Harcourt Electric',
        '7' => 'Ibadan Electric',
        '8' => 'Kaduna Electric',
        '9' => 'Jos Electric',
        '10' => 'Benin Electric',
        '11' => 'Yola Electric',
        '12' => 'Aba Electric',
    ];

    protected static array $discoSlugMap = [
        'ikeja' => 1,
        'ikedc' => 1,
        'eko' => 2,
        'ekedc' => 2,
        'abuja' => 3,
        'aedc' => 3,
        'kano' => 4,
        'kedco' => 4,
        'enugu' => 5,
        'eedc' => 5,
        'port harcourt' => 6,
        'portharcourt' => 6,
        'phed' => 6,
        'phedc' => 6,
        'ibadan' => 7,
        'ibedc' => 7,
        'kaduna' => 8,
        'kaedco' => 8,
        'jos' => 9,
        'jed' => 9,
        'jedplc' => 9,
        'benin' => 10,
        'bedc' => 10,
        'yola' => 11,
        'yedc' => 11,
        'aba' => 12,
        'aple' => 12,
    ];

    /**
     * Resolve Disco input into numeric ID and string name
     */
    public function resolveDisco($discoInput): array
    {
        $input = strtolower(trim((string) $discoInput));
        $clean = trim(str_replace([' electric', ' electricity', ' distribution', ' company', ' power', '-electric', '_electric'], '', $input));

        $id = self::$discoSlugMap[$clean] ?? (self::$discoSlugMap[$input] ?? (is_numeric($discoInput) ? (int)$discoInput : 1));
        $name = self::$discoMap[(string) $id] ?? 'Ikeja Electric';

        return [$id, $name];
    }

    /**
     * Map Frontend Disco Names to Gongoz Disco IDs
     */
    protected function getDiscoId($discoName)
    {
        [$id] = $this->resolveDisco($discoName);
        return $id;
    }

    /**
     * Extract customer name from varied provider API responses
     */
    public static function extractCustomerName(?array $response): ?string
    {
        if (empty($response)) {
            return null;
        }

        $candidates = [
            $response['Customer_Name'] ?? null,
            $response['customer_name'] ?? null,
            $response['name'] ?? null,
            $response['CustomerName'] ?? null,
            $response['customerName'] ?? null,
            $response['user_name'] ?? null,
            $response['account_name'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (!empty($candidate) && is_string($candidate)) {
                $trimmed = trim($candidate);
                $lower = strtolower($trimmed);
                if ($lower !== 'invalid' && $lower !== 'null' && !str_contains($lower, 'invalid meter') && !str_contains($lower, 'not found') && strlen($trimmed) > 1) {
                    return $trimmed;
                }
            }
        }

        return null;
    }

    /**
     * Extract address from varied provider API responses
     */
    public static function extractAddress(?array $response): ?string
    {
        if (empty($response)) {
            return null;
        }

        $candidates = [
            $response['Customer_Address'] ?? null,
            $response['customer_address'] ?? null,
            $response['address'] ?? null,
            $response['CustomerAddress'] ?? null,
            $response['customerAddress'] ?? null,
            $response['meter_address'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (!empty($candidate) && is_string($candidate)) {
                $trimmed = trim($candidate);
                $lower = strtolower($trimmed);
                if ($lower !== 'null' && $lower !== 'n/a' && strlen($trimmed) > 2) {
                    return $trimmed;
                }
            }
        }

        return null;
    }

    /**
     * Buy Electricity
     * 
     * @param int|string $disco Disco ID or Name
     * @param int $amount Amount
     * @param string $meterNumber Meter number
     * @param string $meterType Prepaid or Postpaid
     * @param string $phone Customer phone number
     */
    public function buyElectricity($disco, $amount, $meterNumber, $meterType = 'Prepaid', $phone = '')
    {
        [$discoId, $discoName] = $this->resolveDisco($disco);
        $cleanMeter = preg_replace('/\D/', '', (string) $meterNumber);
        $mType = strtolower($meterType) === 'postpaid' ? 'Postpaid' : 'Prepaid';
        
        $payload = [
            'disco_name' => $discoId,
            'amount' => $amount,
            'meter_number' => $cleanMeter,
            'MeterType' => $mType,
            'Customer_Phone' => $phone
        ];

        return $this->request('post', 'billpayment/', $payload);
    }

    /**
     * Validate Electricity Meter with multi-provider format support and automatic prepaid/postpaid fallback
     * 
     * @param string $meterNumber Meter number
     * @param int|string $disco Disco ID or Name
     * @param string $meterType Prepaid or Postpaid
     */
    public function validateMeter($meterNumber, $disco, $meterType = 'Prepaid')
    {
        $cleanMeter = preg_replace('/\D/', '', (string) $meterNumber);
        [$discoId, $discoName] = $this->resolveDisco($disco);
        $mType = strtolower($meterType) === 'postpaid' ? 'Postpaid' : 'Prepaid';

        // Attempt 1: Query with numeric Disco ID
        $url = 'validatemeter?meternumber=' . urlencode($cleanMeter) . '&disconame=' . urlencode($discoId) . '&mtype=' . urlencode($mType);
        $response = $this->request('get', $url);
        \Illuminate\Support\Facades\Log::info("Gongoz Validate Meter Response (ID: $discoId, Meter: $cleanMeter): ", (array)$response);

        $name = self::extractCustomerName($response);
        if ($name) {
            $response['extracted_name'] = $name;
            $response['extracted_address'] = self::extractAddress($response);
            return $response;
        }

        // Attempt 2: Query with Disco String Name (some gateways expect 'Ikeja Electric')
        $urlName = 'validatemeter?meternumber=' . urlencode($cleanMeter) . '&disconame=' . urlencode($discoName) . '&mtype=' . urlencode($mType);
        $responseName = $this->request('get', $urlName);
        \Illuminate\Support\Facades\Log::info("Gongoz Validate Meter Response (Name: $discoName, Meter: $cleanMeter): ", (array)$responseName);

        $name = self::extractCustomerName($responseName);
        if ($name) {
            $responseName['extracted_name'] = $name;
            $responseName['extracted_address'] = self::extractAddress($responseName);
            return $responseName;
        }

        // Attempt 3: Automatic Alternate Meter Type Fallback (Prepaid <-> Postpaid)
        $altType = $mType === 'Prepaid' ? 'Postpaid' : 'Prepaid';
        $altUrl = 'validatemeter?meternumber=' . urlencode($cleanMeter) . '&disconame=' . urlencode($discoId) . '&mtype=' . urlencode($altType);
        $altResponse = $this->request('get', $altUrl);
        \Illuminate\Support\Facades\Log::info("Gongoz Validate Meter Alt Fallback (Alt: $altType, Meter: $cleanMeter): ", (array)$altResponse);

        $altName = self::extractCustomerName($altResponse);
        if ($altName) {
            $altResponse['extracted_name'] = $altName;
            $altResponse['extracted_address'] = self::extractAddress($altResponse);
            $altResponse['detectedMismatch'] = true;
            $altResponse['suggestedMeterType'] = $altType;
            return $altResponse;
        }

        return $response ?: $responseName;
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
