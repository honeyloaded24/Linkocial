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

        return view('wallet.index', compact('isForeigner', 'cfCountry'));
    }


    /**
     * Initialize a Korapay Deposit (Naira)
     */
    public function initializeKorapay(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01', 
        ]);

        $user = $request->user();
        $currencyCode = session('currency_code', $user->currency_code ?? 'USD');
        $ngnCurrency = \App\Models\Currency::where('code', 'NGN')->first() ?? (object)['exchange_rate' => 1450.0];
        $currentCurrency = \App\Models\Currency::where('code', $currencyCode)->first() ?? \App\Models\Currency::where('is_default', true)->first();

        // 1. Determine the charge currency
        $supportedKoraCurrencies = ['NGN', 'GHS', 'KES'];
        $chargeCurrency = in_array($currentCurrency->code, $supportedKoraCurrencies) ? $currentCurrency->code : 'NGN';
        
        // 2. Calculate the amount in the charge currency
        $amountInUsd = $request->amount / $currentCurrency->exchange_rate;
        if ($amountInUsd < 1) {
            return response()->json(['error' => 'The minimum deposit is $1.00.'], 422);
        }

        if ($chargeCurrency === $currentCurrency->code) {
            $chargeAmount = $request->amount;
        } else {
            $chargeAmount = $amountInUsd * $ngnCurrency->exchange_rate;
        }
        
        $chargeAmount = round($chargeAmount, 2);

        try {
            $reference = 'DEP_KORA_' . time() . '_' . $user->id;
            
            $koraSecret = \App\Models\Setting::get('korapay_mode', 'test') === 'live' ? \App\Models\Setting::get('korapay_live_secret_key') : \App\Models\Setting::get('korapay_test_secret_key');
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
        } elseif (str_starts_with($reference, 'DEP_CRYP_')) {
            $this->verifyHeleketTransaction($reference);
        } else {
            Log::warning('Unknown payment reference format: ' . $reference);
        }
    }

    private function verifyHeleketTransaction($reference)
    {
        // 1. Skip if already processed (Performance & Safety)
        $payment = \App\Models\Payment::where('reference', $reference)->first();
        if (!$payment || $payment->status === 'success') {
            return;
        }

        try {
            $merchantId = \App\Models\Setting::get('heleket_merchant_id');
            $apiKey = \App\Models\Setting::get('heleket_api_key');

            $data = ['order_id' => $reference];
            $jsonData = json_encode($data);
            $sign = md5(base64_encode($jsonData) . $apiKey);

            $response = Http::timeout(15)
                ->retry(3, 200)
                ->withHeaders([
                    'merchant' => $merchantId,
                    'sign' => $sign,
                    'Content-Type' => 'application/json'
                ])->withBody($jsonData, 'application/json')
                ->post('https://api.heleket.com/v1/payment/status');

            $result = $response->json();

            Log::info("Heleket Status Check for {$reference}:", [
                'request_body' => $jsonData,
                'signature' => $sign,
                'response' => $result
            ]);

            $status = strtolower($result['result']['status'] ?? '');
            $isFinal = (bool) ($result['result']['is_final'] ?? false);

            // 2. status === 'paid' means it's successful
            if ($response->successful() && in_array($status, ['paid', 'completed', 'success']) && $isFinal === true) {
                $amount = (float) $result['result']['amount'];
                
                $user = User::find($payment->user_id);
                if ($user) {
                    $this->processSuccessfulHeleketPayment($user, $amount, $reference);
                }
            }
        } catch (\Exception $e) {
            Log::error("Manual Heleket Verification Exception for {$reference}: " . $e->getMessage());
        }
    }


    private function verifyKorapayTransaction($reference)
    {
        try {
            $koraSecret = \App\Models\Setting::get('korapay_mode', 'test') === 'live' ? \App\Models\Setting::get('korapay_live_secret_key') : \App\Models\Setting::get('korapay_test_secret_key');
            $response = Http::withToken($koraSecret)
                ->get("https://api.korapay.com/merchant/api/v1/charges/{$reference}");

            $result = $response->json();

            if ($response->successful() && isset($result['status']) && $result['status'] === true && $result['data']['status'] === 'success') {
                $data = $result['data'];
                $amount = $data['amount'];
                $currency = $data['currency'] ?? 'NGN';
                $customerEmail = $data['customer']['email'] ?? null;

                // Priority 1: Match by reference in Payment record
                $payment = \App\Models\Payment::where('reference', $reference)->first();
                $user = $payment ? User::find($payment->user_id) : null;

                // Priority 2: Fallback to customer email lookup
                if (!$user && $customerEmail) {
                    $user = User::where('email', $customerEmail)->first();
                }

                if ($user) {
                    $this->processSuccessfulPayment($user, $amount, $reference, 'Korapay', $currency);
                } else {
                    Log::error("Korapay Verification: Could not find user for ref {$reference} or email {$customerEmail}");
                }
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
        
        $koraSecret = \App\Models\Setting::get('korapay_mode', 'test') === 'live' ? \App\Models\Setting::get('korapay_live_secret_key') : \App\Models\Setting::get('korapay_test_secret_key');
        $expectedSignature = hash_hmac('sha256', $payload, $koraSecret);
        
        if ($signature !== $expectedSignature) {
            Log::warning('Invalid Korapay Webhook Signature detected.');
            return response()->json(['status' => 'invalid signature'], 400);
        }

        $event = json_decode($payload, true);

        if (($event['event'] ?? '') === 'charge.success' && ($event['data']['status'] ?? '') === 'success') {
            $data = $event['data'];
            $reference = $data['reference'];
            $amount = $data['amount'];
            $currency = $data['currency'] ?? 'NGN';
            $customerEmail = $data['customer']['email'] ?? null;

            if (\App\Models\Payment::where('reference', $reference)->where('status', 'success')->exists()) {
                return response()->json(['status' => 'already processed']);
            }

            DB::beginTransaction();
            try {
                // Priority 1: Match by reference in Payment record
                $payment = \App\Models\Payment::where('reference', $reference)->first();
                $user = $payment ? User::find($payment->user_id) : null;

                // Priority 2: Fallback to customer email lookup
                if (!$user && $customerEmail) {
                    $user = User::where('email', $customerEmail)->first();
                }

                if ($user) {
                    $this->processSuccessfulPayment($user, $amount, $reference, 'Korapay', $currency);
                    DB::commit();
                } else {
                    DB::rollBack();
                    Log::error("Korapay Webhook: Could not find user for ref {$reference} or email {$customerEmail}");
                    return response()->json(['status' => 'user not found'], 404);
                }
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Failed to process Korapay webhook for {$reference}: " . $e->getMessage());
                return response()->json(['status' => 'database error'], 500);
            }
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Shared logic to credit wallet and award commissions
     */
    private function processSuccessfulPayment($user, $amount, $reference, $method = 'Deposit', $currencyCode = 'NGN')
    {
        // 1. Check if this payment reference has already been successfully processed (concurrency check)
        $alreadySuccess = \App\Models\Payment::where('reference', $reference)
            ->where('status', 'success')
            ->exists();

        if ($alreadySuccess) {
            Log::info("Payment reference {$reference} already processed successfully. Skipping duplicate credit.");
            return;
        }

        DB::transaction(function () use ($user, $amount, $reference, $method, $currencyCode) {
            // Re-verify under lock to prevent any race condition double-writes
            $lockedPayment = \App\Models\Payment::where('reference', $reference)
                ->lockForUpdate()
                ->first();

            if ($lockedPayment && $lockedPayment->status === 'success') {
                return;
            }

            // Get the exchange rate for the currency used in the transaction to convert to USD Master
            $transactionCurrency = \App\Models\Currency::where('code', $currencyCode)->first() ?? (object)['exchange_rate' => 1450.0];
            $rate = $transactionCurrency->exchange_rate;
            $amountInUsd = $amount / $rate;

            $ngnCurrency = \App\Models\Currency::where('code', 'NGN')->first() ?? (object)['exchange_rate' => 1450.0];
            $ngnRate = $ngnCurrency->exchange_rate;
            $amountInNaira = $amountInUsd * $ngnRate;

            // Record/Update the payment history (idempotent update)
            \App\Models\Payment::updateOrCreate(
                ['reference' => $reference],
                [
                    'user_id' => $user->id,
                    'amount' => $amount,
                    'currency' => $currencyCode,
                    'amount_usd' => $amountInUsd,
                    'status' => 'success',
                ]
            );

            // Lock and Credit the user's wallet with USD value
            $lockedUser = \App\Models\User::where('id', $user->id)->lockForUpdate()->first();
            $lockedUser->increment('balance', $amountInUsd);

            // --- Promo Bonus Logic (Calculated in USD base) ---
            $promoEnabled = \App\Models\Setting::get('promo_enabled') == '1';
            $promoExpiry = \App\Models\Setting::get('promo_expires_at');
            $promoMinNgn = (float) \App\Models\Setting::get('promo_min_amount', 1000);
            $promoPercent = (float) \App\Models\Setting::get('promo_bonus_percent', 10);
            
            if ($promoEnabled && $promoExpiry && Carbon::parse($promoExpiry)->isFuture()) {
                if ($amountInNaira >= $promoMinNgn) {
                    $bonusAmountUsd = ($amountInUsd * $promoPercent) / 100;
                    $lockedUser->increment('balance', $bonusAmountUsd);
                    
                    \App\Models\Transaction::add(
                        $lockedUser->id, 
                        $bonusAmountUsd, 
                        'credit', 
                        'deposit', 
                        "Promo Bonus (+{$promoPercent}%) for deposit"
                    );
                }
            }

            // 3. Record in Financial Ledger (in USD base)
            \App\Models\Transaction::add(
                $lockedUser->id, 
                $amountInUsd, 
                'credit', 
                'deposit', 
                "Deposit via {$method} (₦" . number_format($amountInNaira, 2) . " / Ref: {$reference})",
                $reference
            );

            // 4. Send Notification & Email to user
            \App\Models\Notification::send(
                $lockedUser->id,
                "Wallet Funded Successfully",
                "Your wallet has been credited with " . money($amountInUsd) . " via {$method}.",
                'success',
                route('wallet.history')
            );

            // Send Wallet Funded Email
            try {
                Mail::send('emails.wallet_funded', [
                    'transaction_id' => 'TXN-' . time(),
                    'method' => $method,
                    'amount' => $amountInNaira,
                    'bonus' => isset($bonusAmountUsd) ? ($bonusAmountUsd * $ngnRate) : 0
                ], function($message) use ($lockedUser) {
                    $message->to($lockedUser->email)->subject("Wallet Funded Successfully! 💰");
                });
            } catch (\Exception $e) {
                Log::error("Deposit email failed: " . $e->getMessage());
            }

            // 5. Referral Commission Logic (Tiered: First vs Recurring)
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
                    $commissionAmountUsd = $amountInUsd * $commissionRate;

                    $referrer->increment('commission_balance', $commissionAmountUsd);
                    $referrer->increment('total_earned_commission', $commissionAmountUsd);
                    
                    \App\Models\AffiliateTransaction::log(
                        $referrer->id, 
                        $commissionAmountUsd, 
                        'credit', 
                        "{$typeLabel} Commission from {$lockedUser->username}"
                    );

                    try {
                        Mail::send('emails.affiliate_commission', [
                            'amount' => ($commissionAmountUsd * $ngnRate),
                            'from_user' => $lockedUser->username
                        ], function($message) use ($referrer) {
                            $message->to($referrer->email)->subject("You just earned money! 💸");
                        });
                    } catch (\Exception $e) {
                        Log::error("Commission email failed: " . $e->getMessage());
                    }

                    \App\Models\ActivityLog::log("Awarded referral commission", $referrer, [
                        'from_user' => $lockedUser->username,
                        'deposit_amount_usd' => $amountInUsd,
                        'commission_usd' => $commissionAmountUsd
                    ]);
                }
            }
        });
    }

    /**
     * Initialize a Heleket Deposit (Crypto/USD)
     */
    public function initializeHeleket(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01', 
        ]);

        $user = $request->user();
        $currencyCode = session('currency_code', $user->currency_code ?? 'USD');
        $currentCurrency = \App\Models\Currency::where('code', $currencyCode)->first() ?? \App\Models\Currency::where('is_default', true)->first();
        
        // Convert the input amount into USD for Heleket and enforce $1 min
        $amountInUsd = $request->amount / $currentCurrency->exchange_rate;
        if ($amountInUsd < 1) {
            return response()->json(['error' => 'The minimum deposit is $1.00.'], 422);
        }

        $merchantId = \App\Models\Setting::get('heleket_merchant_id');
        $apiKey = \App\Models\Setting::get('heleket_api_key');

        $reference = 'DEP_CRYP_' . time() . '_' . $user->id;

        // Strictly documented fields only
        $payload = [
            'amount' => number_format($amountInUsd, 2, '.', ''),
            'currency' => 'USD',
            'order_id' => $reference,
            'email' => $user->email,
            'url_success' => route('wallet.index') . '?status=success&reference=' . $reference,
            'url_callback' => url('/api/webhooks/heleket'),
        ];

        // Create a pending payment record
        \App\Models\Payment::create([
            'user_id' => $user->id,
            'amount' => $amountInUsd,
            'currency' => 'USD',
            'amount_usd' => $amountInUsd,
            'reference' => $reference,
            'status' => 'pending',
            'method' => 'Heleket'
        ]);

        // Heleket Signature Algorithm: Sort keys alphabetically first
        ksort($payload);
        $jsonData = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $sign = md5(base64_encode($jsonData) . $apiKey);

        try {
            $response = Http::timeout(15)
                ->retry(2, 200)
                ->withHeaders([
                    'merchant' => $merchantId,
                    'sign' => $sign,
                    'Content-Type' => 'application/json'
                ])->withBody($jsonData, 'application/json')
                ->post('https://api.heleket.com/v1/payment');

            $result = $response->json();

            Log::info("Heleket Initialization Request:", [
                'body' => $jsonData,
                'sign' => $sign,
                'response' => $result
            ]);

            if ($response->successful() && isset($result['state']) && $result['state'] === 0) {
                return response()->json([
                    'authorization_url' => $result['result']['url'],
                    'uuid' => $result['result']['uuid']
                ]);
            }

            Log::error('Heleket Initialization Failed: ' . $response->body());
            return response()->json(['error' => 'Failed to initialize crypto gateway.'], 500);

        } catch (\Exception $e) {
            Log::error('Heleket Exception: ' . $e->getMessage());
            return response()->json(['error' => 'An error occurred with the crypto gateway.'], 500);
        }
    }

    /**
     * Handle Heleket Webhook
     */
    public function heleketWebhook(Request $request)
    {
        $data = $request->all();
        $rawBody = $request->getContent();
        
        Log::info('Heleket Webhook Received:', [
            'payload' => $data,
            'raw_body' => $rawBody
        ]);
        
        $receivedSign = $data['sign'] ?? '';
        $verificationData = $data;
        unset($verificationData['sign']);
        
        // Removed strict Merchant ID check because Heleket does not include it in the webhook payload.
        // We rely on the cryptographic signature validation below for security.
        $apiKey = \App\Models\Setting::get('heleket_api_key');
        
        // 1. Signature Verification: Heleket signs webhooks in the exact order received (DO NOT SORT)
        $jsonString = json_encode($verificationData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $calculatedSign = md5(base64_encode($jsonString) . $apiKey);

        if ($receivedSign !== $calculatedSign) {
            // Also try signing raw body as fallback
            $fallbackSign = md5(base64_encode($rawBody) . $apiKey);
            
            if ($receivedSign !== $fallbackSign) {
                Log::warning('Invalid Heleket Webhook Signature detected.', [
                    'received' => $receivedSign,
                    'calculated' => $calculatedSign,
                    'fallback' => $fallbackSign,
                    'payload' => $data
                ]);
                return response()->json(['status' => 'invalid signature'], 400);
            }
            Log::info('Heleket Signature Verified via Raw Body.');
        } else {
            Log::info('Heleket Signature Verified via Sorted JSON.');
        }

        $status = strtolower($data['status'] ?? '');
        $isFinal = (bool) ($data['is_final'] ?? false);
        $reference = $data['order_id'] ?? '';

        // 3. Strict Reference Prefix Guard
        if (!str_starts_with($reference, 'DEP_CRYP_')) {
            Log::warning('Heleket Webhook: Invalid Reference Prefix.', ['reference' => $reference]);
            return response()->json(['status' => 'invalid reference'], 400);
        }

        Log::info('Heleket Status Check:', [
            'reference' => $reference,
            'status' => $status,
            'is_final' => $isFinal
        ]);

        // 4. Retrieve and Validate local payment record
        $payment = \App\Models\Payment::where('reference', $reference)->first();
        if (!$payment) {
            Log::error("Heleket Webhook: Reference not found locally: {$reference}");
            return response()->json(['status' => 'not found'], 404);
        }

        // 5. Handle Failed Payments
        if (in_array($status, ['failed', 'canceled', 'expired'])) {
            $payment->update(['status' => 'failed']);
            return response()->json(['status' => 'failure recorded']);
        }

        // 6. Handle Successful Payments
        if (in_array($status, ['paid', 'completed', 'success']) && $isFinal === true) {
            $amountInUsd = (float) ($data['amount'] ?? 0);
            
            // Amount Parity Check
            if (abs($amountInUsd - (float)$payment->amount_usd) > 0.01) {
                Log::warning('Heleket Webhook: Amount mismatch!', [
                    'received' => $amountInUsd,
                    'expected' => $payment->amount_usd
                ]);
            }

            // Idempotency: Skip if already success
            if ($payment->status === 'success') {
                return response()->json(['status' => 'already processed']);
            }

            DB::beginTransaction();
            try {
                $user = User::find($payment->user_id);
                if ($user) {
                    $this->processSuccessfulHeleketPayment($user, $amountInUsd, $reference);
                    DB::commit();
                    Log::info("Heleket Payment Fulfillment Success: {$reference}");
                }
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Failed to process Heleket webhook for {$reference}: " . $e->getMessage());
                return response()->json(['status' => 'database error'], 500);
            }
        }

        return response()->json(['status' => 'success']);
    }

    private function processSuccessfulHeleketPayment($user, $amountInUsd, $reference)
    {
        // 1. Pessimistic Lock on the reference to prevent race conditions
        // This ensures that only ONE process can enter this block for this reference
        $exists = \App\Models\Transaction::where('reference', $reference)
            ->lockForUpdate()
            ->exists();

        if ($exists) {
            Log::info("Heleket Atomic Guard: Transaction already exists for {$reference}, skipping credit.");
            return;
        }

        // 2. Update the payment record
        $payment = \App\Models\Payment::where('reference', $reference)->first();
        if ($payment) {
            $payment->update([
                'amount' => $amountInUsd, 
                'amount_usd' => $amountInUsd,
                'currency' => 'USD',
                'status' => 'success',
                'method' => 'Heleket'
            ]);
        }

        // 3. Credit the user's wallet
        $user->increment('balance', $amountInUsd);

        \App\Models\Transaction::add(
            $user->id, 
            $amountInUsd, 
            'credit', 
            'deposit', 
            "Deposit via Heleket Crypto",
            $reference
        );

        \App\Models\Notification::send(
            $user->id,
            "Crypto Deposit Successful",
            "Your wallet has been credited with " . money($amountInUsd) . " via Heleket.",
            'success',
            route('wallet.history')
        );

        // 4. Send Wallet Funded Email
        try {
            Mail::send('emails.wallet_funded', [
                'transaction_id' => 'TXN-' . time(),
                'method' => 'Heleket Crypto',
                'amount' => $amountInUsd,
                'currency' => 'USD',
                'bonus' => 0
            ], function($message) use ($user) {
                $message->to($user->email)->subject('Wallet Funded Successfully');
            });
        } catch (\Exception $e) {
            Log::error('Heleket Wallet Funded Email Error: ' . $e->getMessage());
        }
    }
}
