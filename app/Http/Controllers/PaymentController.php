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

        return view('wallet.index');
    }

    /**
     * Initialize a Paystack Deposit (Naira)
     */
    public function initializeDeposit(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01', 
        ]);

        $user = $request->user();
        $currencyCode = session('currency_code', $user->currency_code ?? 'USD');
        
        // 1. Convert the user-provided amount into NGN for Paystack
        $ngnCurrency = \App\Models\Currency::where('code', 'NGN')->first() ?? (object)['exchange_rate' => 1450.0];
        $currentCurrency = \App\Models\Currency::where('code', $currencyCode)->first() ?? \App\Models\Currency::where('is_default', true)->first();
        
        $amountInUsd = $request->amount / $currentCurrency->exchange_rate;
        if ($amountInUsd < 1) {
            return response()->json(['error' => 'The minimum deposit is $1.00.'], 422);
        }

        $amountInNgn = $amountInUsd * $ngnCurrency->exchange_rate;
        $amountInKobo = round($amountInNgn * 100); 

        try {
            $response = Http::withToken(config('services.paystack.secret'))
                ->post('https://api.paystack.co/transaction/initialize', [
                    'email' => $user->email,
                    'amount' => $amountInKobo,
                    'reference' => 'DEP_' . time() . '_' . $user->id,
                    'callback_url' => route('wallet.index') . '?status=success',
                ]);

            $result = $response->json();

            if ($response->successful() && $result['status']) {
                return response()->json([
                    'authorization_url' => $result['data']['authorization_url'],
                    'reference' => $result['data']['reference']
                ]);
            }

            Log::error('Paystack Initialization Failed: ' . $response->body());
            return response()->json(['error' => 'Failed to initialize payment gateway.'], 500);

        } catch (\Exception $e) {
            Log::error('Paystack Exception: ' . $e->getMessage());
            return response()->json(['error' => 'An error occurred communicating with the payment gateway.'], 500);
        }
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
            
            $response = Http::withToken(config('services.korapay.secret'))
                ->post('https://api.korapay.com/merchant/api/v1/charges/initialize', [
                    'amount' => $chargeAmount,
                    'currency' => $chargeCurrency,
                    'reference' => $reference,
                    'notification_url' => url('/api/webhooks/korapay'),
                    'redirect_url' => route('wallet.index') . '?status=success&reference=' . $reference,
                    'customer' => [
                        'email' => $user->email,
                        'name' => $user->username,
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

    /**
     * Verify a transaction with the appropriate gateway API
     */
    private function verifyTransaction($reference)
    {
        // Don't re-process if already successful
        if (\App\Models\Payment::where('reference', $reference)->where('status', 'success')->exists()) {
            return;
        }

        if (str_starts_with($reference, 'DEP_KORA_')) {
            $this->verifyKorapayTransaction($reference);
        } else {
            $this->verifyPaystackTransaction($reference);
        }
    }

    private function verifyPaystackTransaction($reference)
    {
        try {
            $response = Http::withToken(config('services.paystack.secret'))
                ->get("https://api.paystack.co/transaction/verify/{$reference}");

            $result = $response->json();

            if ($response->successful() && $result['status'] && $result['data']['status'] === 'success') {
                $data = $result['data'];
                $amountInNaira = $data['amount'] / 100;
                $customerEmail = $data['customer']['email'];
                $user = User::where('email', $customerEmail)->first();

                if ($user) {
                    $this->processSuccessfulPayment($user, $amountInNaira, $reference, 'Paystack');
                }
            }
        } catch (\Exception $e) {
            Log::error("Manual Paystack Verification Failed for {$reference}: " . $e->getMessage());
        }
    }

    private function verifyKorapayTransaction($reference)
    {
        try {
            $response = Http::withToken(config('services.korapay.secret'))
                ->get("https://api.korapay.com/merchant/api/v1/charges/{$reference}");

            $result = $response->json();

            if ($response->successful() && isset($result['status']) && $result['status'] === true && $result['data']['status'] === 'success') {
                $data = $result['data'];
                $amount = $data['amount'];
                $currency = $data['currency'] ?? 'NGN';
                $customerEmail = $data['customer']['email'];
                $user = User::where('email', $customerEmail)->first();

                if ($user) {
                    $this->processSuccessfulPayment($user, $amount, $reference, 'Korapay', $currency);
                }
            }
        } catch (\Exception $e) {
            Log::error("Manual Korapay Verification Failed for {$reference}: " . $e->getMessage());
        }
    }

    /**
     * Handle Paystack Webhook for successful payments
     */
    public function webhook(Request $request)
    {
        // 1. Verify webhook signature for security
        $signature = $request->header('x-paystack-signature');
        $payload = $request->getContent();
        
        $expectedSignature = hash_hmac('sha512', $payload, config('services.paystack.secret'));
        
        if ($signature !== $expectedSignature) {
            Log::warning('Invalid Paystack Webhook Signature detected.');
            return response()->json(['status' => 'invalid signature'], 400);
        }

        $event = json_decode($payload, true);

        // 2. Process only successful charge events
        if ($event['event'] === 'charge.success') {
            $data = $event['data'];
            $reference = $data['reference'];
            $amountInNaira = $data['amount'] / 100;
            $customerEmail = $data['customer']['email'];

            // Don't re-process if already handled by manual verification
            if (\App\Models\Payment::where('reference', $reference)->where('status', 'success')->exists()) {
                return response()->json(['status' => 'already processed']);
            }

            DB::beginTransaction();
            try {
                $user = User::where('email', $customerEmail)->first();
                if ($user) {
                    $this->processSuccessfulPayment($user, $amountInNaira, $reference);
                    DB::commit();
                }
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Failed to process Paystack webhook for {$reference}: " . $e->getMessage());
                return response()->json(['status' => 'database error'], 500);
            }
        }

        // Always return 200 OK to Paystack so they stop retrying the webhook
        return response()->json(['status' => 'success']);
    }

    /**
     * Handle Korapay Webhook
     */
    public function korapayWebhook(Request $request)
    {
        $signature = $request->header('x-korapay-signature');
        $payload = $request->getContent();
        
        $expectedSignature = hash_hmac('sha256', $payload, config('services.korapay.secret'));
        
        if ($signature !== $expectedSignature) {
            Log::warning('Invalid Korapay Webhook Signature detected.');
            return response()->json(['status' => 'invalid signature'], 400);
        }

        $event = json_decode($payload, true);

        if ($event['event'] === 'charge.success' && $event['data']['status'] === 'success') {
            $data = $event['data'];
            $reference = $data['reference'];
            $amount = $data['amount'];
            $currency = $data['currency'] ?? 'NGN';
            $customerEmail = $data['customer']['email'];

            if (\App\Models\Payment::where('reference', $reference)->where('status', 'success')->exists()) {
                return response()->json(['status' => 'already processed']);
            }

            DB::beginTransaction();
            try {
                $user = User::where('email', $customerEmail)->first();
                if ($user) {
                    $this->processSuccessfulPayment($user, $amount, $reference, 'Korapay', $currency);
                    DB::commit();
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
    private function processSuccessfulPayment($user, $amount, $reference, $method = 'Paystack', $currencyCode = 'NGN')
    {
        // Get the exchange rate for the currency used in the transaction to convert to USD Master
        $transactionCurrency = \App\Models\Currency::where('code', $currencyCode)->first() ?? (object)['exchange_rate' => 1450.0];
        $rate = $transactionCurrency->exchange_rate;
        $amountInUsd = $amount / $rate;

        // 1. Record the payment history (store the original amount and currency for records)
        \App\Models\Payment::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'currency' => $currencyCode,
            'amount_usd' => $amountInUsd,
            'reference' => $reference,
            'status' => 'success',
        ]);

        // 2. Credit the user's wallet with USD value
        $user->increment('balance', $amountInUsd);

        // --- Promo Bonus Logic (Calculated in USD base) ---
        $promoEnabled = \App\Models\Setting::get('promo_enabled') == '1';
        $promoExpiry = \App\Models\Setting::get('promo_expires_at');
        $promoMinNgn = (float) \App\Models\Setting::get('promo_min_amount', 1000);
        $promoPercent = (float) \App\Models\Setting::get('promo_bonus_percent', 10);
        
        if ($promoEnabled && $promoExpiry && Carbon::parse($promoExpiry)->isFuture()) {
            if ($amountInNaira >= $promoMinNgn) {
                $bonusAmountUsd = ($amountInUsd * $promoPercent) / 100;
                $user->increment('balance', $bonusAmountUsd);
                
                \App\Models\Transaction::add(
                    $user->id, 
                    $bonusAmountUsd, 
                    'credit', 
                    'deposit', 
                    "Promo Bonus (+{$promoPercent}%) for deposit"
                );
            }
        }

        // 3. Record in Financial Ledger (in USD base)
        \App\Models\Transaction::add(
            $user->id, 
            $amountInUsd, 
            'credit', 
            'deposit', 
            "Deposit via {$method} (₦" . number_format($amountInNaira, 2) . " / Ref: {$reference})",
            $reference
        );

        // 4. Send Notification & Email to user (Formatted in their local currency)
        \App\Models\Notification::send(
            $user->id,
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
                'amount' => $amountInNaira, // Email still shows NGN as that's what they paid
                'bonus' => isset($bonusAmountUsd) ? ($bonusAmountUsd * $ngnRate) : 0
            ], function($message) use ($user) {
                $message->to($user->email)->subject("Wallet Funded Successfully! 💰");
            });
        } catch (\Exception $e) {
            Log::error("Deposit email failed: " . $e->getMessage());
        }

        // 5. Referral Commission Logic (Tiered: First vs Recurring)
        if ($user->referred_by) {
            $referrer = User::find($user->referred_by);
            if ($referrer) {
                // Check if this is the user's first successful payment
                // We count successful payments. If count == 1 (this one), it's the first.
                $paymentCount = \App\Models\Payment::where('user_id', $user->id)->where('status', 'success')->count();
                
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
                    "{$typeLabel} Commission from {$user->username}"
                );

                // Send Commission Email (Show in their preferred currency if possible, or just NGN for now as hint)
                try {
                    Mail::send('emails.affiliate_commission', [
                        'amount' => ($commissionAmountUsd * $ngnRate), // Show NGN value for clarity
                        'from_user' => $user->username
                    ], function($message) use ($referrer) {
                        $message->to($referrer->email)->subject("You just earned money! 💸");
                    });
                } catch (\Exception $e) {
                    Log::error("Commission email failed: " . $e->getMessage());
                }

                \App\Models\ActivityLog::log("Awarded referral commission", $referrer, [
                    'from_user' => $user->username,
                    'deposit_amount_usd' => $amountInUsd,
                    'commission_usd' => $commissionAmountUsd
                ]);
            }
        }
        
        Log::info("Payment Processed: Credited " . number_format($amountInUsd, 4) . " USD to User ID {$user->id}");
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

        $merchantId = config('services.heleket.merchant_id');
        $apiKey = config('services.heleket.key');

        $data = [
            'amount' => number_format($amountInUsd, 2, '.', ''),
            'currency' => 'USD',
            'order_id' => 'DEP_CRYP_' . time() . '_' . $user->id,
            'email' => $user->email,
        ];

        // Heleket Signature Algorithm: md5(base64_encode(json_encode($data)) . $API_KEY)
        $jsonData = json_encode($data);
        $sign = md5(base64_encode($jsonData) . $apiKey);

        try {
            $response = Http::withHeaders([
                'merchant' => $merchantId,
                'sign' => $sign,
                'Content-Type' => 'application/json'
            ])->post('https://api.heleket.com/v1/payment', $data);

            $result = $response->json();

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
        $receivedSign = $data['sign'] ?? '';
        
        $verificationData = $data;
        unset($verificationData['sign']);
        
        $apiKey = config('services.heleket.key');
        
        $jsonString = json_encode($verificationData, JSON_UNESCAPED_UNICODE);
        $calculatedSign = md5(base64_encode($jsonString) . $apiKey);

        if ($receivedSign !== $calculatedSign) {
            Log::warning('Invalid Heleket Webhook Signature detected.');
            return response()->json(['status' => 'invalid signature'], 400);
        }

        if ($data['status'] === 'paid' && $data['is_final'] === true) {
            $reference = $data['order_id'];
            $amountInUsd = (float) $data['amount'];
            
            if (\App\Models\Payment::where('reference', $reference)->where('status', 'success')->exists()) {
                return response()->json(['status' => 'already processed']);
            }

            DB::beginTransaction();
            try {
                $refParts = explode('_', $reference);
                $userId = $refParts[count($refParts)-1] ?? 0;
                $user = User::find($userId);
                
                if ($user) {
                    $this->processSuccessfulHeleketPayment($user, $amountInUsd, $reference);
                    DB::commit();
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
        \App\Models\Payment::create([
            'user_id' => $user->id,
            'amount' => $amountInUsd, 
            'reference' => $reference,
            'status' => 'success',
        ]);

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
    }
}
