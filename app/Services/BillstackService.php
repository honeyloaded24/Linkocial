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
    public function generateVirtualAccount(User $user, string $bank = '9PSB', ?string $bvn = null, bool $forceNew = false): array
    {
        $bank = strtoupper(trim($bank));

        if (!in_array($bank, ['9PSB', 'PALMPAY'])) {
            return [
                'success' => false,
                'message' => 'Invalid bank selected. Only 9PSB and PalmPay are supported.'
            ];
        }

        // Return existing active account if already generated (unless forcing new or cleaning Customer User)
        $existing = UserVirtualAccount::where('user_id', $user->id)
            ->where('bank_id', $bank)
            ->where('status', 'active')
            ->first();

        if ($existing && !empty($existing->account_number) && !$forceNew) {
            if (!str_contains($existing->account_name, 'Customer User')) {
                return [
                    'success' => true,
                    'account' => $existing,
                    'message' => 'Account already generated.'
                ];
            }
        }

        if (empty($this->secretKey)) {
            Log::error('BillStack: Secret key is not configured.');
            return [
                'success' => false,
                'message' => 'Virtual account provider is currently not configured by admin.'
            ];
        }

        // Parse Name from user attributes
        $firstName = trim($user->first_name ?? '');
        $lastName = trim($user->last_name ?? '');

        if (empty($firstName) && empty($lastName)) {
            $firstName = trim($user->username ?: 'Customer');
            $lastName = 'User';
        } elseif (empty($lastName)) {
            $lastName = trim($user->username ?: $firstName);
        } elseif (empty($firstName)) {
            $firstName = trim($user->username ?: $lastName);
        }

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
                $accountName = $acc['account_name'] ?? $data['account_name'] ?? ($firstName . ' ' . $lastName);
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

            $rawErrMsg = $result['message'] ?? $result['error'] ?? 'Unable to generate virtual account.';
            Log::error("Virtual Account API Error: {$rawErrMsg}", ['response' => $result]);
            
            // Cleanse error message so internal provider names or raw technical details are never exposed
            $userMessage = $rawErrMsg;
            if (preg_match('/\b(billstack|wiaxy|provider|api|token|secret|endpoint|sql|db)\b/i', $rawErrMsg)) {
                $userMessage = 'Unable to generate virtual account at this time. Please check your BVN/details or try again shortly.';
            }

            return [
                'success' => false,
                'message' => $userMessage
            ];

        } catch (\Exception $e) {
            Log::error("Virtual Account Generation Exception for user {$user->id}: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Unable to generate virtual account right now. Please try again later.'
            ];
        }
    }

    /**
     * Verify BillStack webhook signature.
     *
     * @param string $rawBody
     * @param string|null $signature256 Header x-wiaxy-signature-256
     * @param string|null $timestamp Header x-wiaxy-timestamp
     * @param string|null $signatureMd5 Header x-wiaxy-signature (Legacy)
     * @return bool
     */
    public function verifyWebhookSignature(string $rawBody, ?string $signature256, ?string $timestamp = null, ?string $signatureMd5 = null): bool
    {
        if (empty($this->secretKey)) {
            Log::error('BillStack Webhook: Secret key is not configured.');
            return false;
        }

        // Allow bypassing signature in local testing if no signature provided
        if (app()->environment('local', 'testing') && empty($signature256) && empty($signatureMd5)) {
            return true;
        }

        // 1. Verify HMAC-SHA256 with timestamp (Official BillStack specification)
        if (!empty($signature256) && !empty($timestamp)) {
            $expectedSignatureWithTs = hash_hmac('sha256', "{$timestamp}.{$rawBody}", $this->secretKey);
            if (hash_equals($expectedSignatureWithTs, $signature256)) {
                return true;
            }
        }

        // 2. Fallback: HMAC-SHA256 of raw payload alone
        if (!empty($signature256)) {
            $expectedSignaturePlain = hash_hmac('sha256', $rawBody, $this->secretKey);
            if (hash_equals($expectedSignaturePlain, $signature256)) {
                return true;
            }
        }

        // 3. Fallback: Legacy static md5(secret_key)
        if (!empty($signatureMd5)) {
            $expectedMd5 = md5($this->secretKey);
            if (hash_equals($expectedMd5, $signatureMd5)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Retrieve transaction details from BillStack by session_id / transaction_ref.
     *
     * @param string $sessionId
     * @return array
     */
    public function getTransaction(string $sessionId): array
    {
        if (empty($this->secretKey)) {
            return [
                'success' => false,
                'message' => 'BillStack secret key not configured.'
            ];
        }

        try {
            $endpoint = rtrim($this->baseUrl, '/') . '/thirdparty/getTransaction/';
            $response = Http::withToken($this->secretKey)
                ->timeout(20)
                ->post($endpoint, [
                    'session_id' => trim($sessionId),
                ]);

            $result = $response->json();

            if ($response->successful() && (($result['status'] ?? false) === true || isset($result['data']['amount']))) {
                return [
                    'success' => true,
                    'data' => $result['data'] ?? [],
                    'message' => $result['message'] ?? 'Transaction retrieved.'
                ];
            }

            return [
                'success' => false,
                'message' => $result['message'] ?? 'Transaction not found or query failed.'
            ];
        } catch (\Exception $e) {
            Log::error("BillStack getTransaction Exception: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Network error connecting to BillStack API.'
            ];
        }
    }
}
