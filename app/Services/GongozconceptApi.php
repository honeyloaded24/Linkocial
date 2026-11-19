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
        $rawKey = \App\Models\Setting::get('gongozconcept_api_key', config('services.gongozconcept.api_key'));
        $cleanKey = trim(preg_replace('/^(Token|Bearer)\s+/i', '', (string)$rawKey));

        if (empty($cleanKey)) {
            Log::error('Gongozconcept API key is missing. Please set it in HQ Settings.');
            return [
                'status' => false,
                'message' => 'VTU Provider configuration error: Gongozconcept API key is missing. Please set it in HQ Settings.',
            ];
        }

        $url = rtrim($this->apiUrl, '/') . '/' . ltrim($endpoint, '/');

        $headers = [
            'Authorization' => 'Token ' . $cleanKey,
            'Accept' => 'application/json',
        ];

        if (strtolower($method) !== 'get') {
            $headers['Content-Type'] = 'application/json';
        }

        try {
            $http = Http::timeout(15)->withHeaders($headers);
            if (strtolower($method) === 'get') {
                $response = $http->get($url);
            } else {
                $response = $http->$method($url, $data);
            }

            if ($response->status() === 401 || $response->status() === 403) {
                Log::warning("Gongozconcept API Unauthorized (HTTP {$response->status()}): Invalid API token. Check HQ Settings.");
                return [
                    'status' => false,
                    'message' => 'VTU Provider authentication error: Invalid or expired API token. Please verify your Gongozconcept API key in HQ Settings.',
                    'detail' => 'Invalid token.',
                ];
            }

            $json = $response->json();
            return is_array($json) ? $json : ['status' => $response->successful(), 'body' => $response->body()];
        } catch (\Exception $e) {
            Log::error('Gongozconcept API error: ' . $e->getMessage());
            return [
                'status' => false,
                'ambiguous' => true,
                'message' => 'Provider request outcome could not be confirmed. Please check order status before retrying.',
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
     * Complete Nigerian Electricity Distribution Companies (DisCos) Metadata
     */
    protected static array $discoDefinitions = [
        1 => ['id' => 1, 'name' => 'Ikeja Electric', 'code' => 'IKEDC', 'slug' => 'ikeja-electric'],
        2 => ['id' => 2, 'name' => 'Eko Electric', 'code' => 'EKEDC', 'slug' => 'eko-electric'],
        3 => ['id' => 3, 'name' => 'Abuja Electric', 'code' => 'AEDC', 'slug' => 'abuja-electric'],
        4 => ['id' => 4, 'name' => 'Kano Electric', 'code' => 'KEDCO', 'slug' => 'kano-electric'],
        5 => ['id' => 5, 'name' => 'Enugu Electric', 'code' => 'EEDC', 'slug' => 'enugu-electric'],
        6 => ['id' => 6, 'name' => 'Port Harcourt Electric', 'code' => 'PHED', 'slug' => 'port-harcourt-electric'],
        7 => ['id' => 7, 'name' => 'Ibadan Electric', 'code' => 'IBEDC', 'slug' => 'ibadan-electric'],
        8 => ['id' => 8, 'name' => 'Kaduna Electric', 'code' => 'KAEDCO', 'slug' => 'kaduna-electric'],
        9 => ['id' => 9, 'name' => 'Jos Electric', 'code' => 'JED', 'slug' => 'jos-electric'],
        10 => ['id' => 10, 'name' => 'Benin Electric', 'code' => 'BEDC', 'slug' => 'benin-electric'],
        11 => ['id' => 11, 'name' => 'Yola Electric', 'code' => 'YEDC', 'slug' => 'yola-electric'],
        12 => ['id' => 12, 'name' => 'Aba Electric', 'code' => 'APLE', 'slug' => 'aba-electric'],
    ];

    /**
     * Resolve DisCo input into full detail array (id, name, code, slug)
     */
    public function resolveDiscoDetails($discoInput): array
    {
        if (is_numeric($discoInput) && isset(self::$discoDefinitions[(int)$discoInput])) {
            return self::$discoDefinitions[(int)$discoInput];
        }

        $str = ' ' . strtolower(trim((string)$discoInput)) . ' ';

        // 1. Kaduna / KAEDCO (Checked before AEDC to prevent partial substring match)
        if (str_contains($str, 'kaduna') || str_contains($str, 'kaedco')) return self::$discoDefinitions[8];
        // 2. Ikeja / IKEDC
        if (str_contains($str, 'ikeja') || str_contains($str, 'ikedc')) return self::$discoDefinitions[1];
        // 3. Eko / EKEDC
        if (str_contains($str, 'eko') || str_contains($str, 'ekedc')) return self::$discoDefinitions[2];
        // 4. Abuja / AEDC
        if (str_contains($str, 'abuja') || str_contains($str, 'aedc')) return self::$discoDefinitions[3];
        // 5. Kano / KEDCO
        if (str_contains($str, 'kano') || str_contains($str, 'kedco')) return self::$discoDefinitions[4];
        // 6. Enugu / EEDC
        if (str_contains($str, 'enugu') || str_contains($str, 'eedc')) return self::$discoDefinitions[5];
        // 7. Port Harcourt / PHED / PHEDC
        if (str_contains($str, 'port') || str_contains($str, 'harcourt') || str_contains($str, 'phed')) return self::$discoDefinitions[6];
        // 8. Ibadan / IBEDC
        if (str_contains($str, 'ibadan') || str_contains($str, 'ibedc')) return self::$discoDefinitions[7];
        // 9. Jos / JED / JEDPLC
        if (str_contains($str, 'jos') || str_contains($str, 'jed')) return self::$discoDefinitions[9];
        // 10. Benin / BEDC
        if (str_contains($str, 'benin') || str_contains($str, 'bedc')) return self::$discoDefinitions[10];
        // 11. Yola / YEDC
        if (str_contains($str, 'yola') || str_contains($str, 'yedc')) return self::$discoDefinitions[11];
        // 12. Aba / APLE
        if (str_contains($str, 'aba') || str_contains($str, 'aple')) return self::$discoDefinitions[12];

        return self::$discoDefinitions[1];
    }

    /**
     * Resolve Disco input into numeric ID and string name
     */
    public function resolveDisco($discoInput): array
    {
        $details = $this->resolveDiscoDetails($discoInput);
        return [$details['id'], $details['name']];
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
     * Extract customer name from varied provider API responses (including nested data)
     */
    public static function extractCustomerName($response): ?string
    {
        if (empty($response) || !is_array($response)) {
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
            $response['customer'] ?? null,
            $response['Customer'] ?? null,
            $response['meter_name'] ?? null,
            $response['meterName'] ?? null,
            // Nested checks inside data / details
            $response['data']['Customer_Name'] ?? null,
            $response['data']['customer_name'] ?? null,
            $response['data']['name'] ?? null,
            $response['data']['CustomerName'] ?? null,
            $response['data']['customer'] ?? null,
            $response['details']['Customer_Name'] ?? null,
            $response['details']['customer_name'] ?? null,
            $response['details']['name'] ?? null,
        ];

        $invalidWords = ['invalid', 'null', 'n/a', 'not found', 'invalid meter', 'error', 'unknown', 'failed'];

        foreach ($candidates as $candidate) {
            if (!empty($candidate) && is_string($candidate)) {
                $trimmed = trim($candidate);
                $lower = strtolower($trimmed);
                if (!in_array($lower, $invalidWords) && !str_contains($lower, 'invalid meter') && strlen($trimmed) > 1) {
                    return $trimmed;
                }
            }
        }

        return null;
    }

    /**
     * Extract address from varied provider API responses
     */
    public static function extractAddress($response): ?string
    {
        if (empty($response) || !is_array($response)) {
            return null;
        }

        $candidates = [
            $response['Customer_Address'] ?? null,
            $response['customer_address'] ?? null,
            $response['address'] ?? null,
            $response['CustomerAddress'] ?? null,
            $response['customerAddress'] ?? null,
            $response['meter_address'] ?? null,
            $response['data']['Customer_Address'] ?? null,
            $response['data']['customer_address'] ?? null,
            $response['data']['address'] ?? null,
            $response['details']['address'] ?? null,
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
     * Validate Electricity Meter with multi-provider format support, aliases, and automatic prepaid/postpaid fallback
     * 
     * @param string $meterNumber Meter number
     * @param int|string $disco Disco ID or Name
     * @param string $meterType Prepaid or Postpaid
     */
    public function validateMeter($meterNumber, $disco, $meterType = 'Prepaid')
    {
        $cleanMeter = preg_replace('/\D/', '', (string) $meterNumber);
        if (strlen($cleanMeter) < 6) {
            return [
                'status' => false,
                'message' => 'Please enter a valid meter number (at least 6 digits).'
            ];
        }

        $discoInfo = $this->resolveDiscoDetails($disco);
        $discoId = $discoInfo['id'];
        $discoName = $discoInfo['name'];
        $discoCode = $discoInfo['code'];

        $isPostpaid = strtolower($meterType) === 'postpaid' || $meterType === '2';
        $mTypeCap = $isPostpaid ? 'Postpaid' : 'Prepaid';
        $mTypeLow = $isPostpaid ? 'postpaid' : 'prepaid';
        $mTypeNum = $isPostpaid ? 2 : 1;

        $altTypeCap = $isPostpaid ? 'Prepaid' : 'Postpaid';
        $altTypeLow = $isPostpaid ? 'prepaid' : 'postpaid';
        $altTypeNum = $isPostpaid ? 1 : 2;

        // Helper to query validatemeter endpoint with full parameter aliases
        $queryEndpoint = function ($endpoint, $discoVal, $typeCap, $typeLow, $typeNum) use ($cleanMeter) {
            $qs = http_build_query([
                'meternumber' => $cleanMeter,
                'meter_number' => $cleanMeter,
                'disconame' => $discoVal,
                'disco_name' => $discoVal,
                'provider' => $discoVal,
                'disco' => $discoVal,
                'mtype' => $typeCap,
                'metertype' => $typeLow,
                'MeterType' => $typeCap,
                'type' => $typeNum,
            ]);
            return $this->request('get', "{$endpoint}?{$qs}");
        };

        // Strategy 1: Standard validatemeter/ with Numeric Disco ID (e.g. 1)
        $res = $queryEndpoint('validatemeter/', $discoId, $mTypeCap, $mTypeLow, $mTypeNum);
        Log::info("Validate Meter Strategy 1 (ID: $discoId, Meter: $cleanMeter): ", (array)$res);
        $name = self::extractCustomerName($res);
        if ($name) {
            $res['extracted_name'] = $name;
            $res['extracted_address'] = self::extractAddress($res);
            return $res;
        }

        // If the token is invalid, stop immediately and return the auth error
        if (isset($res['detail']) && strtolower($res['detail']) === 'invalid token.') {
            return $res;
        }

        // Strategy 2: Query with DisCo Code/Acronym (e.g. IKEDC, AEDC, KAEDCO)
        $resCode = $queryEndpoint('validatemeter/', $discoCode, $mTypeCap, $mTypeLow, $mTypeNum);
        Log::info("Validate Meter Strategy 2 (Code: $discoCode, Meter: $cleanMeter): ", (array)$resCode);
        $name = self::extractCustomerName($resCode);
        if ($name) {
            $resCode['extracted_name'] = $name;
            $resCode['extracted_address'] = self::extractAddress($resCode);
            return $resCode;
        }

        // Strategy 3: Query with DisCo Full Name (e.g. Ikeja Electric)
        $resName = $queryEndpoint('validatemeter/', $discoName, $mTypeCap, $mTypeLow, $mTypeNum);
        Log::info("Validate Meter Strategy 3 (Name: $discoName, Meter: $cleanMeter): ", (array)$resName);
        $name = self::extractCustomerName($resName);
        if ($name) {
            $resName['extracted_name'] = $name;
            $resName['extracted_address'] = self::extractAddress($resName);
            return $resName;
        }

        // Strategy 4: Automatic Alternate Meter Type Fallback (Prepaid <-> Postpaid)
        $resAlt = $queryEndpoint('validatemeter/', $discoId, $altTypeCap, $altTypeLow, $altTypeNum);
        Log::info("Validate Meter Strategy 4 (Alt Type: $altTypeCap, Meter: $cleanMeter): ", (array)$resAlt);
        $name = self::extractCustomerName($resAlt);
        if ($name) {
            $resAlt['extracted_name'] = $name;
            $resAlt['extracted_address'] = self::extractAddress($resAlt);
            $resAlt['detectedMismatch'] = true;
            $resAlt['suggestedMeterType'] = $altTypeCap;
            return $resAlt;
        }

        // Strategy 5: Fallback to /ajax/validate_meter_number/ endpoint
        $resAjax = $queryEndpoint('ajax/validate_meter_number/', $discoId, $mTypeCap, $mTypeLow, $mTypeNum);
        Log::info("Validate Meter Strategy 5 (Ajax, Meter: $cleanMeter): ", (array)$resAjax);
        $name = self::extractCustomerName($resAjax);
        if ($name) {
            $resAjax['extracted_name'] = $name;
            $resAjax['extracted_address'] = self::extractAddress($resAjax);
            return $resAjax;
        }

        return $res ?: ($resCode ?: ($resName ?: $resAlt));
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
