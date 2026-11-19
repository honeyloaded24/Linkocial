<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use App\Models\User;

class PaymentController extends Controller
{
    /**
     * Show the Add Funds / Deposit view
     */
    public function deposit(Request $request)
    {
        // Fallback verification if webhook hasn't fired yet
        if ($request->filled('reference')) {
            $this->verifyTransaction($request->reference);
        }

        // Determine user location via Cloudflare header (default to NG for local dev/testing)
        $cfCountry = $request->header('CF-IPCountry', 'NG');
        $isForeigner = $cfCountry !== 'NG';

        $user = $request->user();
        $virtualAccounts = collect();
        try {
            if ($user && \Illuminate\Support\Facades\Schema::hasTable('user_virtual_accounts')) {
                $virtualAccounts = $user->virtualAccounts()->get()->keyBy('bank_id');
            }
        } catch (\Throwable $e) {
            $virtualAccounts = collect();
        }
        $account9psb = $virtualAccounts->get('9PSB');
        $accountPalmpay = $virtualAccounts->get('PALMPAY');
        $depositFee = (float) \App\Models\Setting::get('billstack_deposit_fee', 45.0);

        return view('wallet.index', compact('isForeigner', 'cfCountry', 'account9psb', 'accountPalmpay', 'depositFee'));
    }


    /**
     * Initialize a Korapay Deposit (Naira)
     */
    public function initializeKorapay(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:100', 
        ]);

        $user = $request->user();
        $chargeAmount = round((float) $request->amount, 2);
        $chargeCurrency = 'NGN';

        if ($chargeAmount < 100) {
            return response()->json(['error' => 'The minimum deposit is ₦100.00.'], 422);
        }

        try {
            $reference = 'DEP_KORA_' . time() . '_' . $user->id;
            
            $koraSecret = \App\Models\Setting::get('korapay_mode', 'test') === 'live' ? \App\Models\Setting::get('korapay_live_secret_key') : \App\Models\Setting::get('korapay_test_secret_key');
            $koraSecret = trim($koraSecret ?? '');
            $response = Http::withToken($koraSecret)
                ->post('https://api.korapay.com/merchant/api/v1/charges/initialize', [
                    'amount' => $chargeAmount,
                    'currency' => $chargeCurrency,
                    'reference' => $reference,
                    'notification_url' => url('/api/webhooks/korapay'),
                    'redirect_url' => route('wallet.index') . '?status=success&reference=' . $reference,
                    'customer' => [
                        'email' => $user->email,
                        'name' => trim($user->first_name . ' ' . $user->last_name) ?: $user->username,
                    ],
                    'merchant_bears_cost' => false
                ]);

            $result = $response->json();

            if ($response->successful() && isset($result['status']) && $result['status'] === true) {
                // Create a pending payment record so webhook/callbacks always match the user
                \App\Models\Payment::create([
                    'user_id' => $user->id,
                    'amount' => $chargeAmount,
                    'currency' => $chargeCurrency,
                    'amount_usd' => 0,
                    'reference' => $reference,
                    'status' => 'pending',
                    'method' => 'Korapay'
                ]);

                return response()->json([
                    'checkout_url' => $result['data']['checkout_url'],
                    'reference' => $reference
                ]);
            }

            Log::error('Korapay Initialization Failed: ' . $response->body());
            return response()->json(['error' => 'Failed to initialize Korapay.'], 500);

        } catch (\Exception $e) {
            Log::error('Korapay Exception: ' . $e->getMessage());
            return response()->json(['error' => 'An error occurred with Korapay.'], 500);
        }
    }

    private function verifyTransaction($reference)
    {
        // Don't re-process if already successful
        if (\App\Models\Payment::where('reference', $reference)->where('status', 'success')->exists()) {
            return;
        }
        if (str_starts_with($reference, 'DEP_KORA_')) {
            $this->verifyKorapayTransaction($reference);
        } else {
            Log::warning('Unknown payment reference format: ' . $reference);
        }
    }


    private function verifyKorapayTransaction($reference)
    {
        try {
            $koraSecret = \App\Models\Setting::get('korapay_mode', 'test') === 'live' ? \App\Models\Setting::get('korapay_live_secret_key') : \App\Models\Setting::get('korapay_test_secret_key');
            $koraSecret = trim($koraSecret ?? '');
            $response = Http::withToken($koraSecret)
                ->get("https://api.korapay.com/merchant/api/v1/charges/{$reference}");

            $result = $response->json();

            if ($response->successful() && isset($result['status']) && $result['status'] === true && ($result['data']['status'] ?? '') === 'success') {
                $data = $result['data'];
                $amount = (float) $data['amount'];
                $currency = strtoupper($data['currency'] ?? 'NGN');
                $customerEmail = $data['customer']['email'] ?? null;

                // Enforce NGN currency
                if ($currency !== 'NGN') {
                    Log::warning("Korapay Verification: Rejected transaction {$reference} with currency {$currency} (expected NGN).");
                    return;
                }

                // Require an exact pending deposit record and match the confirmed amount.
                $payment = \\App\\Models\\Payment::where('reference', $reference)->first();
                if (!$payment || $payment->status !== 'pending') {
                    Log::warning("Korapay Verification: No pending payment found for {$reference}.");
                    return;
                }

                if (round((float) $payment->amount, 2) !== round($amount, 2)
                    || strtoupper((string) $payment->currency) !== 'NGN') {
                    Log::error("Korapay Verification: Amount/currency mismatch for {$reference}", [
                        'expected_amount' => $payment->amount,
                        'received_amount' => $amount,
                        'expected_currency' => $payment->currency,
                        'received_currency' => $currency,
                    ]);
                    return;
                }

                $user = User::find($payment->user_id);
                if (!$user) {
                    Log::error("Korapay Verification: Payment owner missing for {$reference}.");
                    return;
                }

                $this->processSuccessfulPayment($user, $amount, $reference, 'Korapay', 'NGN');
            }
        } catch (\Exception $e) {
            Log::error("Manual Korapay Verification Failed for {$reference}: " . $e->getMessage());
        }
    }


    /**
     * Handle Korapay Webhook
     */
    public function korapayWebhook(Request $request)
    {
        $signature = $request->header('x-korapay-signature');
        $payload = $request->getContent();
        
        Log::info('Korapay Webhook Received:', [
            'has_signature' => !empty($signature),
            'payload_length' => strlen($payload)
        ]);

        $koraSecret = \App\Models\Setting::get('korapay_mode', 'test') === 'live' ? \App\Models\Setting::get('korapay_live_secret_key') : \App\Models\Setting::get('korapay_test_secret_key');
        $koraSecret = trim($koraSecret ?? '');
        
        if (empty($koraSecret)) {
            Log::error('Korapay Webhook: Secret key is not configured.');
            return response()->json(['status' => 'webhook secret unconfigured'], 403);
        }

        $expectedSignature = hash_hmac('sha256', $payload, $koraSecret);
        
        if (empty($signature) || !hash_equals((string) $expectedSignature, (string) $signature)) {
            Log::warning('Invalid Korapay Webhook Signature detected.', [
                'received' => $signature,
                'expected' => $expectedSignature
            ]);
            return response()->json(['status' => 'invalid signature'], 400);
        }

        $event = json_decode($payload, true);

        if (($event['event'] ?? '') === 'charge.success' && ($event['data']['status'] ?? '') === 'success') {
            $data = $event['data'];
            $reference = $data['reference'];
            $amount = (float) $data['amount'];
            $currency = strtoupper($data['currency'] ?? 'NGN');
            $customerEmail = $data['customer']['email'] ?? null;

            // Enforce NGN
            if ($currency !== 'NGN') {
                Log::warning("Korapay Webhook: Non-NGN transaction currency {$currency} rejected for reference {$reference}.");
                return response()->json(['status' => 'unsupported currency'], 400);
            }

            if (\App\Models\Payment::where('reference', $reference)->where('status', 'success')->exists()) {
                return response()->json(['status' => 'already processed']);
            }

            DB::beginTransaction();
            try {
            // Require exact reference matching; never credit by email or parsed user ID.
            $payment = \\App\\Models\\Payment::where('reference', $reference)->first();
            if (!$payment) {
                Log::error("Korapay Webhook: No pending payment record for {$reference}");
                return response()->json(['status' => 'payment not found'], 404);
            }

            if ($payment->status === 'success') {
                return response()->json(['status' => 'already processed']);
            }

            if ($payment->status !== 'pending'
                || round((float) $payment->amount, 2) !== round($amount, 2)
                || strtoupper((string) $payment->currency) !== 'NGN') {
                Log::error("Korapay Webhook: Payment state/amount/currency mismatch for {$reference}", [
                    'payment_status' => $payment->status,
                    'expected_amount' => $payment->amount,
                    'received_amount' => $amount,
                    'expected_currency' => $payment->currency,
                    'received_currency' => $currency,
                ]);
                return response()->json(['status' => 'payment mismatch'], 400);
            }

            $user = User::find($payment->user_id);
            if (!$user) {
                Log::error("Korapay Webhook: Payment owner missing for {$reference}");
                return response()->json(['status' => 'user not found'], 404);
            }

            DB::beginTransaction();
            try {
                $this->processSuccessfulPayment($user, $amount, $reference, 'Korapay', 'NGN');
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Failed to process Korapay webhook for {$reference}: " . $e->getMessage());
                return response()->json(['status' => 'database error'], 500);
            }
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Shared logic to credit wallet and award commissions (All in NGN)
     */
    public function processSuccessfulPayment($user, $amount, $reference, $method = 'Deposit', $currencyCode = 'NGN', $fee = 0.0)
    {
        $currencyCode = strtoupper($currencyCode ?? 'NGN');
        $conversionService = app(\App\Services\CurrencyConversionService::class);
        $rateUsed = 1.0;
        $originalAmount = round((float) $amount, 2);

        if ($currencyCode === 'USD') {
            $rateUsed = $conversionService->getUsdToNgnRate();
            $ngnAmount = $conversionService->usdToNgn($originalAmount);
            Log::info("Payment in USD verified for ref {$reference}. Converted {$originalAmount} USD to ₦{$ngnAmount} NGN at rate 1 USD = ₦{$rateUsed}");
        } elseif ($currencyCode === 'NGN') {
            $ngnAmount = $originalAmount;
        } else {
            Log::warning("Payment Rejected: unsupported currency {$currencyCode} for ref {$reference}. Supported: NGN, USD.");
            return;
        }

        // 1. Check if this payment reference has already been successfully processed (concurrency check)
        $alreadySuccess = \App\Models\Payment::where('reference', $reference)
            ->where('status', 'success')
            ->exists();

        if ($alreadySuccess) {
            Log::info("Payment reference {$reference} already processed successfully. Skipping duplicate credit.");
            return;
        }

        DB::transaction(function () use ($user, $ngnAmount, $originalAmount, $currencyCode, $rateUsed, $reference, $method, $fee) {
            // Re-verify under lock to prevent any race condition double-writes
            $lockedPayment = \App\Models\Payment::where('reference', $reference)
                ->lockForUpdate()
                ->first();

            if ($lockedPayment && $lockedPayment->status === 'success') {
                return;
            }

            // Record/Update the payment history in NGN
            \App\Models\Payment::updateOrCreate(
                ['reference' => $reference],
                [
                    'user_id' => $user->id,
                    'amount' => $ngnAmount,
                    'currency' => 'NGN',
                    'amount_usd' => $currencyCode === 'USD' ? $originalAmount : round($ngnAmount / $rateUsed, 4),
                    'status' => 'success',
                ]
            );

            // Lock and Credit the user's wallet directly in NGN
            $lockedUser = \App\Models\User::where('id', $user->id)->lockForUpdate()->first();
            $lockedUser->addBalance($ngnAmount);

            // --- Promo Bonus Logic (in NGN) ---
            $promoEnabled = \App\Models\Setting::get('promo_enabled') == '1';
            $promoExpiry = \App\Models\Setting::get('promo_expires_at');
            $promoMinNgn = (float) \App\Models\Setting::get('promo_min_amount', 1000);
            $promoPercent = (float) \App\Models\Setting::get('promo_bonus_percent', 10);
            
            $bonusAmount = 0.0;
            if ($promoEnabled && $promoExpiry && Carbon::parse($promoExpiry)->isFuture()) {
                if ($ngnAmount >= $promoMinNgn) {
                    $bonusAmount = round(($ngnAmount * $promoPercent) / 100, 2);
                    $lockedUser->addBalance($bonusAmount);
                    
                    \App\Models\Transaction::add(
                        $lockedUser->id, 
                        $bonusAmount, 
                        'credit', 
                        'deposit', 
                        "Promo Bonus (+{$promoPercent}%) for deposit"
                    );
                }
            }

            // 3. Record in Financial Ledger (in NGN)
            \App\Models\Transaction::add(
                $lockedUser->id, 
                $ngnAmount, 
                'credit', 
                'deposit', 
                "Deposit via {$method} (₦" . number_format($ngnAmount, 2) . ($currencyCode === 'USD' ? " / \${$originalAmount} @ ₦{$rateUsed}" : "") . " / Ref: {$reference})",
                $reference
            );

            // 4. Send Notification & Email to user
            \App\Models\Notification::send(
                $lockedUser->id,
                "Wallet Funded Successfully",
                "Your wallet has been credited with " . money($ngnAmount) . " via {$method}.",
                'success',
                route('wallet.history')
            );

            // Send Wallet Funded Email
            try {
                Mail::send('emails.wallet_funded', [
                    'transaction_id' => 'TXN-' . time(),
                    'method' => $method,
                    'amount' => $ngnAmount,
                    'bonus' => $bonusAmount
                ], function($message) use ($lockedUser) {
                    $message->to($lockedUser->email)->subject("Wallet Funded Successfully! 💰");
                });
            } catch (\Exception $e) {
                Log::error("Deposit email failed: " . $e->getMessage());
            }

            // 5. Referral Commission Logic (in NGN)
            if ($lockedUser->referred_by) {
                $referrer = User::find($lockedUser->referred_by);
                if ($referrer) {
                    $paymentCount = \App\Models\Payment::where('user_id', $lockedUser->id)->where('status', 'success')->count();
                    
                    if ($paymentCount <= 1) {
                        $rate = (float) \App\Models\Setting::get('affiliate_commission_first', 10);
                        $typeLabel = "First Deposit";
                    } else {
                        $rate = (float) \App\Models\Setting::get('affiliate_commission_recurring', 5);
                        $typeLabel = "Recurring";
                    }

                    $commissionRate = $rate / 100;
                    $commissionAmount = round($ngnAmount * $commissionRate, 2);

                    $referrer->increment('commission_balance', $commissionAmount);
                    $referrer->increment('total_earned_commission', $commissionAmount);
                    
                    \App\Models\AffiliateTransaction::log(
                        $referrer->id, 
                        $commissionAmount, 
                        'credit', 
                        "{$typeLabel} Commission from {$lockedUser->username}"
                    );

                    try {
                        Mail::send('emails.affiliate_commission', [
                            'amount' => $commissionAmount,
                            'from_user' => $lockedUser->username
                        ], function($message) use ($referrer) {
                            $message->to($referrer->email)->subject("You just earned money! 💸");
                        });
                    } catch (\Exception $e) {
                        Log::error("Commission email failed: " . $e->getMessage());
                    }

                    \App\Models\ActivityLog::log("Awarded referral commission", $referrer, [
                        'from_user' => $lockedUser->username,
                        'deposit_amount_ngn' => $amount,
                        'commission_ngn' => $commissionAmount
                    ]);
                }
            }
        });
    }

    /**
     * Generate a dedicated BillStack virtual account for the authenticated user.
     */
    public function generateVirtualAccount(Request $request, \App\Services\BillstackService $billstackService)
    {
        $request->validate([
            'bank' => 'required|string|in:9PSB,PALMPAY,9psb,palmpay',
            'bvn' => 'nullable|string'
        ]);

        $user = $request->user();
        $bank = strtoupper($request->bank);
        $bvn = $request->bvn ? trim($request->bvn) : null;

        if ($bank === 'PALMPAY') {
            if (empty($bvn) || strlen($bvn) !== 11 || !ctype_digit($bvn)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please provide a valid 11-digit BVN for PalmPay virtual account generation.'
                ], 422);
            }
        }

        $forceNew = $request->boolean('force_new');
        $result = $billstackService->generateVirtualAccount($user, $bank, $bvn, $forceNew);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Failed to generate virtual account.'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'] ?? 'Virtual account ready.',
            'account' => $result['account']
        ]);
    }

    /**
     * Handle BillStack Webhook for Automated Virtual Account Crediting
     */
    public function billstackWebhook(Request $request, \App\Services\BillstackService $billstackService)
    {
        $signature256 = $request->header('x-wiaxy-signature-256') ?: $request->header('X-Wiaxy-Signature-256');
        $signatureMd5 = $request->header('x-wiaxy-signature') ?: $request->header('X-Wiaxy-Signature');
        $timestamp = $request->header('x-wiaxy-timestamp') ?: $request->header('X-Wiaxy-Timestamp');
        $rawBody = $request->getContent();

        Log::info('BillStack Webhook Received:', [
            'has_sig256' => !empty($signature256),
            'has_sigmd5' => !empty($signatureMd5),
            'timestamp' => $timestamp,
            'payload_length' => strlen($rawBody)
        ]);

        if (!$billstackService->verifyWebhookSignature($rawBody, $signature256, $timestamp, $signatureMd5)) {
            Log::warning('Invalid BillStack Webhook Signature detected.', [
                'sig256' => $signature256,
                'sigmd5' => $signatureMd5,
                'timestamp' => $timestamp
            ]);
            return response()->json(['status' => false, 'message' => 'Invalid signature'], 400);
        }

        $payload = json_decode($rawBody, true) ?: $request->all();
        Log::info('BillStack Webhook Payload:', ['payload' => $payload]);

        $event = strtoupper(trim((string)($payload['event'] ?? '')));
        $data = $payload['data'] ?? $payload;
        $dataType = strtoupper(trim((string)($data['type'] ?? '')));
        $status = strtolower(trim((string)($data['status'] ?? $payload['status'] ?? '')));

        // Check if this is a payment notification
        $isPayment = ($event === 'PAYMENT_NOTIFICATION')
            || ($dataType === 'RESERVED_ACCOUNT_TRANSACTION')
            || in_array($status, ['success', 'successful', 'paid', 'completed'])
            || (isset($payload['status']) && $payload['status'] === true && !empty($data['amount']));

        if (!$isPayment) {
            Log::info('BillStack Webhook: Non-payment event ignored', [
                'event' => $event,
                'type' => $dataType,
                'status' => $status
            ]);
            return response()->json(['status' => true, 'message' => 'Non-payment event ignored'], 200);
        }

        $amount = (float) ($data['amount'] ?? $data['settled_amount'] ?? $payload['amount'] ?? 0);
        if ($amount <= 0) {
            Log::warning('BillStack Webhook: Zero or negative amount received.', ['data' => $data]);
            return response()->json(['status' => true, 'message' => 'Zero amount ignored'], 200);
        }

        // Interbank transaction reference (transaction_ref / wiaxy_ref)
        $txId = $data['transaction_ref'] ?? $data['wiaxy_ref'] ?? $data['session_id'] ?? $data['transaction_reference'] ?? null;
        $vaRef = $data['merchant_reference'] ?? $data['reference'] ?? null;
        $accountNumber = $data['account']['account_number'] ?? $data['account_number'] ?? null;
        $bankName = $data['account']['bank_name'] ?? $data['bank_name'] ?? 'Virtual Account';
        $customerEmail = $data['customer']['email'] ?? $data['customer'] ?? $data['email'] ?? null;

        // A provider transaction identifier is required for replay-safe webhook processing.
        // Never synthesize an idempotency key from the current time or virtual account.
        if (empty($txId) || !is_scalar($txId) || trim((string) $txId) === '') {
            Log::warning('BillStack Webhook rejected: missing stable transaction identifier.', [
                'account_number' => $accountNumber,
                'merchant_reference' => $vaRef,
            ]);
            return response()->json(['status' => false, 'message' => 'Missing transaction identifier'], 422);
        }

        $ref = 'BS_' . trim((string) $txId);

        // Idempotency: Skip if already processed
        if (\App\Models\Payment::where('reference', $ref)->where('status', 'success')->exists()) {
            Log::info("BillStack Webhook: Payment {$ref} already processed.");
            return response()->json(['status' => true, 'message' => 'Already processed'], 200);
        }

        // Match User
        $user = null;
        if (!empty($accountNumber)) {
            $cleanAcc = trim((string)$accountNumber);
            $va = \App\Models\UserVirtualAccount::where('account_number', $cleanAcc)->first();
            if ($va) {
                $user = $va->user;
                if (!empty($va->bank_name)) {
                    $bankName = $va->bank_name;
                }
            }
        }

        if (!$user && !empty($vaRef)) {
            $cleanVaRef = trim((string)$vaRef);
            $va = \App\Models\UserVirtualAccount::where('reference', $cleanVaRef)->first();
            if ($va) {
                $user = $va->user;
                if (!empty($va->bank_name)) {
                    $bankName = $va->bank_name;
                }
            }
        }

        if (!$user && !empty($customerEmail) && is_string($customerEmail)) {
            $user = User::where('email', trim($customerEmail))->first();
        }

        if (!$user) {
            Log::error("BillStack Webhook: User not found for account {$accountNumber}, ref {$vaRef}, or email {$customerEmail}");
            return response()->json(['status' => false, 'message' => 'User not found'], 404);
        }

        // Calculate deposit fee (Default 45 Naira if unconfigured)
        $depositFee = (float) \App\Models\Setting::get('billstack_deposit_fee', 45.0);
        $netAmount = round($amount - $depositFee, 2);

        if ($netAmount <= 0) {
            Log::warning("BillStack Webhook: Deposit amount NGN {$amount} is <= fee NGN {$depositFee}. Ref: {$ref}");
            return response()->json(['status' => true, 'message' => 'Deposit amount absorbed by fee'], 200);
        }

        try {
            // Process wallet credit with atomic concurrency protection
            $this->processSuccessfulPayment($user, $netAmount, $ref, "Virtual Account ({$bankName})", 'NGN', $depositFee);
            Log::info("BillStack Webhook: Successfully credited user {$user->id} ({$user->username}) with net NGN {$netAmount} after NGN {$depositFee} fee (Ref: {$ref})");
            return response()->json(['status' => true, 'message' => 'successful'], 200);
        } catch (\Exception $e) {
            Log::error("BillStack Webhook Processing Error for {$ref}: " . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Internal processing error'], 500);
        }
    }
}
