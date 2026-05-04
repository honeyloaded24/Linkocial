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
            'amount' => 'required|numeric|min:100', // Minimum deposit of 100 NGN
        ]);

        $user = $request->user();
        $amountInKobo = $request->amount * 100; // Paystack processes amounts in Kobo

        try {
            $response = Http::withToken(env('PAYSTACK_SECRET_KEY'))
                ->post('https://api.paystack.co/transaction/initialize', [
                    'email' => $user->email,
                    'amount' => $amountInKobo,
                    'reference' => 'DEP_' . time() . '_' . $user->id,
                    'callback_url' => route('wallet.index') . '?status=success',
                ]);

            $result = $response->json();

            if ($response->successful() && $result['status']) {
                // Return the checkout URL to the frontend
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
     * Verify a transaction with Paystack API
     */
    private function verifyTransaction($reference)
    {
        // Don't re-process if already successful
        if (\App\Models\Payment::where('reference', $reference)->where('status', 'success')->exists()) {
            return;
        }

        try {
            $response = Http::withToken(env('PAYSTACK_SECRET_KEY'))
                ->get("https://api.paystack.co/transaction/verify/{$reference}");

            $result = $response->json();

            if ($response->successful() && $result['status'] && $result['data']['status'] === 'success') {
                $data = $result['data'];
                $amountInNaira = $data['amount'] / 100;
                $customerEmail = $data['customer']['email'];
                $user = User::where('email', $customerEmail)->first();

                if ($user) {
                    $this->processSuccessfulPayment($user, $amountInNaira, $reference);
                }
            }
        } catch (\Exception $e) {
            Log::error("Manual Verification Failed for {$reference}: " . $e->getMessage());
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
        
        $expectedSignature = hash_hmac('sha512', $payload, env('PAYSTACK_SECRET_KEY'));
        
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
     * Shared logic to credit wallet and award commissions
     */
    private function processSuccessfulPayment($user, $amountInNaira, $reference)
    {
        // 1. Record the payment history
        \App\Models\Payment::create([
            'user_id' => $user->id,
            'amount' => $amountInNaira,
            'reference' => $reference,
            'status' => 'success',
        ]);

        // 2. Credit the user's wallet
        $user->increment('balance', $amountInNaira);

        // --- Promo Bonus Logic ---
        $promoEnabled = \App\Models\Setting::get('promo_enabled') == '1';
        $promoExpiry = \App\Models\Setting::get('promo_expires_at');
        $promoMin = (float) \App\Models\Setting::get('promo_min_amount', 1000);
        $promoPercent = (float) \App\Models\Setting::get('promo_bonus_percent', 10);
        
        if ($promoEnabled && $promoExpiry && Carbon::parse($promoExpiry)->isFuture()) {
            if ($amountInNaira >= $promoMin) {
                $bonusAmount = ($amountInNaira * $promoPercent) / 100;
                $user->increment('balance', $bonusAmount);
                
                \App\Models\Transaction::add(
                    $user->id, 
                    $bonusAmount, 
                    'credit', 
                    'deposit', 
                    "Promo Bonus (+{$promoPercent}%) for deposit"
                );
            }
        }

        // 3. Record in Financial Ledger
        \App\Models\Transaction::add(
            $user->id, 
            $amountInNaira, 
            'credit', 
            'deposit', 
            "Deposit via Paystack (Ref: {$reference})",
            $reference
        );

        // 4. Send Notification & Email to user
        \App\Models\Notification::send(
            $user->id,
            "Wallet Funded Successfully",
            "Your wallet has been credited with ₦" . number_format($amountInNaira, 2) . " via Paystack.",
            'success',
            route('wallet.history')
        );

        // Send Wallet Funded Email
        try {
            Mail::send('emails.wallet_funded', [
                'transaction_id' => 'TXN-' . time(),
                'method' => 'Paystack',
                'amount' => $amountInNaira,
                'bonus' => isset($bonusAmount) ? $bonusAmount : 0
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
                $commissionAmount = $amountInNaira * $commissionRate;

                $referrer->increment('commission_balance', $commissionAmount);
                $referrer->increment('total_earned_commission', $commissionAmount);
                
                \App\Models\AffiliateTransaction::log(
                    $referrer->id, 
                    $commissionAmount, 
                    'credit', 
                    "{$typeLabel} Commission from {$user->username}"
                );

                // Send Commission Email
                try {
                    Mail::send('emails.affiliate_commission', [
                        'amount' => $commissionAmount,
                        'from_user' => $user->username
                    ], function($message) use ($referrer) {
                        $message->to($referrer->email)->subject("You just earned money! 💸");
                    });
                } catch (\Exception $e) {
                    Log::error("Commission email failed: " . $e->getMessage());
                }

                \App\Models\ActivityLog::log("Awarded referral commission", $referrer, [
                    'from_user' => $user->username,
                    'deposit_amount' => $amountInNaira,
                    'commission' => $commissionAmount
                ]);
            }
        }
        
        Log::info("Payment Processed: Credited {$amountInNaira} NGN to User ID {$user->id}");
    }
}
