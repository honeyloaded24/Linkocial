<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\UserVirtualAccount;
use App\Models\Payment;
use App\Services\BillstackService;
use App\Http\Controllers\PaymentController;

class SyncBillstackTransaction extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'billstack:sync-transaction {session_id : The session_id or transaction_ref from BillStack}';

    /**
     * The console command description.
     */
    protected $description = 'Query BillStack by session_id/transaction_ref and manually settle an uncredited virtual account payment';

    /**
     * Execute the console command.
     */
    public function handle(BillstackService $billstackService, PaymentController $paymentController)
    {
        $sessionId = trim($this->argument('session_id'));
        $this->info("Querying BillStack for transaction: {$sessionId}...");

        $res = $billstackService->getTransaction($sessionId);

        if (!$res['success']) {
            $this->error("Failed to query BillStack: " . ($res['message'] ?? 'Unknown error'));
            return 1;
        }

        $data = $res['data'] ?? [];
        $this->line(json_encode($data, JSON_PRETTY_PRINT));

        $status = strtoupper($data['status'] ?? '');
        if (!in_array($status, ['SUCCESS', 'SUCCESSFUL', 'PAID', 'COMPLETED'])) {
            $this->warn("Transaction status on BillStack is '{$status}' (not successful). Skipping credit.");
            return 1;
        }

        $amount = (float)($data['amount'] ?? 0);
        if ($amount <= 0) {
            $this->error("Invalid transaction amount: {$amount}");
            return 1;
        }

        $txId = $data['transaction_ref'] ?? $data['session_id'] ?? $sessionId;
        $ref = 'BS_' . trim($txId);

        // Check if already processed
        if (Payment::where('reference', $ref)->where('status', 'success')->exists()) {
            $this->warn("Payment reference {$ref} has already been credited in Linkocial.");
            return 0;
        }

        // Match user by account / nuban / reference / customer email
        $user = null;
        $accountNumber = $data['nuban'] ?? $data['account_number'] ?? null;
        $vaRef = $data['reference'] ?? null;
        $customerEmail = $data['customer'] ?? $data['email'] ?? null;
        $bankName = $data['bank'] ?? 'Virtual Account';

        if (!empty($accountNumber)) {
            $va = UserVirtualAccount::where('account_number', trim((string)$accountNumber))->first();
            if ($va && $va->user) {
                $user = $va->user;
                $bankName = $va->bank_name ?: $bankName;
            }
        }

        if (!$user && !empty($vaRef)) {
            $va = UserVirtualAccount::where('reference', trim((string)$vaRef))->first();
            if ($va && $va->user) {
                $user = $va->user;
                $bankName = $va->bank_name ?: $bankName;
            }
        }

        if (!$user && !empty($customerEmail)) {
            $user = User::where('email', trim($customerEmail))->first();
        }

        if (!$user) {
            $this->error("Could not find matching Linkocial user for account '{$accountNumber}' or reference '{$vaRef}'.");
            $inputUserId = $this->ask("Enter User ID to manually credit this payment (or leave blank to cancel):");
            if (empty($inputUserId)) {
                return 1;
            }
            $user = User::find($inputUserId);
            if (!$user) {
                $this->error("User #{$inputUserId} not found.");
                return 1;
            }
        }

        $depositFee = (float) \App\Models\Setting::get('billstack_deposit_fee', 45.0);
        $netAmount = round($amount - $depositFee, 2);

        if ($netAmount <= 0) {
            $this->error("Deposit amount NGN {$amount} is less than or equal to the deposit fee NGN {$depositFee}.");
            return 1;
        }

        $this->info("Crediting User #{$user->id} ({$user->username} - {$user->email}) with net NGN {$netAmount} (Gross: NGN {$amount}, Fee: NGN {$depositFee})...");

        try {
            // The shared payment processor requires a pending payment record to lock
            // and serialize settlement. Recovery must establish that record first.
            $payment = Payment::firstOrCreate(
                ['reference' => $ref],
                [
                    'user_id' => $user->id,
                    'amount' => $netAmount,
                    'currency' => 'NGN',
                    'amount_usd' => 0,
                    'status' => 'pending',
                ]
            );

            if ((int) $payment->user_id !== (int) $user->id) {
                $this->error("Payment reference {$ref} is already associated with a different user. Refusing credit.");
                return 1;
            }

            if ($payment->status === 'success') {
                $this->warn("Payment reference {$ref} has already been credited.");
                return 0;
            }

            if ($payment->status !== 'pending') {
                $this->error("Payment reference {$ref} is in status '{$payment->status}'. Refusing credit.");
                return 1;
            }

            $paymentController->processSuccessfulPayment($user, $netAmount, $ref, "Virtual Account ({$bankName})", 'NGN', $depositFee);
            $this->info("SUCCESS! User #{$user->id} credited with net NGN {$netAmount} (Ref: {$ref}).");
            return 0;
        } catch (\Exception $e) {
            $this->error("Error processing credit: " . $e->getMessage());
            return 1;
        }
    }
}
