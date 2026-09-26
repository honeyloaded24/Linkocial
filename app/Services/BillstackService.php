<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\User;
use App\Models\UserVirtualAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BillstackService
{
    protected string $baseUrl;
    protected ?string $secretKey;

    public function __construct()
    {
        $this->baseUrl = config('services.billstack.base_url', 'https://api.billstack.co/v2');
        $this->secretKey = Setting::get('billstack_secret_key') ?: config('services.billstack.secret_key');
        if ($this->secretKey) {
            $this->secretKey = trim($this->secretKey);
        }
    }

    /**
     * Get the active secret key.
     */
    public function getSecretKey(): ?string
    {
        return $this->secretKey;
    }

    /**
     * Generate or retrieve a dedicated virtual account for a user.
     *
     * @param User $user
     * @param string $bank '9PSB' or 'PALMPAY'
     * @param string|null $bvn Required when $bank is 'PALMPAY'
     * @return array
     */
    public function generateVirtualAccount(User $user, string $bank = '9PSB', ?string $bvn = null): array
    {
        $bank = strtoupper(trim($bank));

        if (!in_array($bank, ['9PSB', 'PALMPAY'])) {
            return [
                'success' => false,
                'message' => 'Invalid bank selected. Only 9PSB and PalmPay are supported.'
            ];
        }

        // Return existing active account if already generated
        $existing = UserVirtualAccount::where('user_id', $user->id)
            ->where('bank_id', $bank)
            ->where('status', 'active')
            ->first();

        if ($existing && !empty($existing->account_number)) {
            return [
                'success' => true,
                'account' => $existing,
                'message' => 'Account already generated.'
            ];
        }

        if (empty($this->secretKey)) {
            Log::error('BillStack: Secret key is not configured.');
            return [
                'success' => false,
                'message' => 'Virtual account provider is currently not configured by admin.'
            ];
        }

        // Parse Name
        $nameParts = explode(' ', trim($user->name ?? 'Customer User'));
        $firstName = $nameParts[0] ?? 'User';
        $lastName = isset($nameParts[1]) ? implode(' ', array_slice($nameParts, 1)) : ($user->username ?: 'Customer');

        // Parse Phone
        $phone = preg_replace('/[^0-9]/', '', (string)($user->phone ?? ''));
        if (str_starts_with($phone, '234') && strlen($phone) > 10) {
            $phone = '0' . substr($phone, 3);
        }
        if (empty($phone) || strlen($phone) < 10) {
            $phone = '080' . substr(str_pad((string)$user->id, 8, '0', STR_PAD_LEFT), -8);
        }

        $reference = 'linkocial_' . $user->id . '_' . strtolower($bank) . '_' . time();

        $payload = [
            'reference' => $reference,
            'email' => $user->email,
            'phone' => $phone,
            'firstName' => $firstName,
            'lastName' => $lastName,
            'bank' => $bank,
        ];

        if ($bank === 'PALMPAY') {
            if (empty($bvn) || strlen(trim($bvn)) !== 11 || !ctype_digit(trim($bvn))) {
                return [
                    'success' => false,
                    'message' => 'A valid 11-digit BVN is required to generate a PalmPay virtual account.'
                ];
            }
            $payload['idType'] = 'bvn';
            $payload['idNumber'] = trim($bvn);
        }

        try {
            Log::info("BillStack Virtual Account Request for User {$user->id} ({$bank}):", [
                'reference' => $reference,
                'bank' => $bank,
                'email' => $user->email
            ]);

            $endpoint = rtrim($this->baseUrl, '/') . '/thirdparty/generateVirtualAccount/';
            $response = Http::withToken($this->secretKey)
                ->timeout(20)
                ->post($endpoint, $payload);

            $result = $response->json();

            Log::info("BillStack Response for User {$user->id}:", [
                'status' => $response->status(),
                'body' => $result
            ]);

            $isSuccess = $response->successful() && (
                ($result['status'] ?? false) === true ||
                ($result['success'] ?? false) === true ||
                isset($result['data']['account']) ||
                isset($result['data']['account_number'])
            );

            if ($isSuccess && isset($result['data'])) {
                $data = $result['data'];

                // Handle account array or object
                $acc = null;
                if (isset($data['account']) && is_array($data['account'])) {
                    $acc = isset($data['account'][0]) ? $data['account'][0] : $data['account'];
                } else {
                    $acc = $data;
                }

                $accountNumber = $acc['account_number'] ?? $data['account_number'] ?? null;
                $accountName = $acc['account_name'] ?? $data['account_name'] ?? ($user->name);
                $bankName = $acc['bank_name'] ?? ($bank === 'PALMPAY' ? 'PalmPay' : '9Payment Service Bank (9PSB)');
                $apiRef = $data['reference'] ?? $reference;

                if (empty($accountNumber)) {
                    Log::error("BillStack Account generation did not return account_number: " . json_encode($result));
                    return [
                        'success' => false,
                        'message' => $result['message'] ?? 'Failed to retrieve account number from provider.'
                    ];
                }

                // Mask BVN for security storage
                $maskedBvn = $bvn ? (substr($bvn, 0, 3) . '*****' . substr($bvn, -3)) : null;

                $virtualAccount = UserVirtualAccount::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'bank_id' => $bank
                    ],
                    [
                        'bank_name' => $bankName,
                        'account_number' => $accountNumber,
                        'account_name' => $accountName,
                        'reference' => $apiRef,
                        'bvn' => $maskedBvn,
                        'status' => 'active',
                        'meta' => $data
                    ]
                );

                return [
                    'success' => true,
                    'account' => $virtualAccount,
                    'message' => 'Virtual account generated successfully.'
                ];
            }

            $errMsg = $result['message'] ?? $result['error'] ?? 'Provider error generating virtual account.';
            Log::error("BillStack Virtual Account Error: {$errMsg}", ['response' => $result]);
            return [
                'success' => false,
                'message' => $errMsg
            ];

        } catch (\Exception $e) {
            Log::error("BillStack Generation Exception for user {$user->id}: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Could not connect to virtual account provider. Please try again later.'
            ];
        }
    }

    /**
     * Verify BillStack webhook signature.
     *
     * @param string $rawBody
     * @param string|null $signature Header x-wiaxy-signature-256
     * @param string|null $timestamp Header x-wiaxy-timestamp
     * @return bool
     */
    public function verifyWebhookSignature(string $rawBody, ?string $signature, ?string $timestamp = null): bool
    {
        if (empty($this->secretKey) || empty($signature)) {
            return false;
        }

        // BillStack specification: HMAC-SHA256 of "$timestamp.$rawBody"
        if (!empty($timestamp)) {
            $expectedSignatureWithTs = hash_hmac('sha256', "{$timestamp}.{$rawBody}", $this->secretKey);
            if (hash_equals($expectedSignatureWithTs, $signature)) {
                return true;
            }
        }

        // Fallback: direct HMAC of raw payload
        $expectedSignaturePlain = hash_hmac('sha256', $rawBody, $this->secretKey);
        return hash_equals($expectedSignaturePlain, $signature);
    }
}
