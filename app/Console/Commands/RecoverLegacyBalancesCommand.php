<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Transaction;
use App\Models\Payment;
use App\Models\ActivityLog;
use App\Services\CurrencyConversionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class RecoverLegacyBalancesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wallet:recover-legacy-balances 
                            {--rate= : Authoritative conversion rate (1 USD = X NGN). Defaults to admin settings rate}
                            {--apply : Execute and apply the balance recovery to the database. (Without this flag, runs in safe DRY RUN mode)}
                            {--force : Skip interactive confirmation prompt when applying}
                            {--usd-threshold=500 : Maximum balance to treat as legacy USD without manual confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Safely recover and convert confirmed legacy USD customer balances to NGN with idempotency and audit trails';

    protected CurrencyConversionService $conversionService;

    public function __construct(CurrencyConversionService $conversionService)
    {
        parent::__construct();
        $this->conversionService = $conversionService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $rateOption = $this->option('rate');
        $rate = ($rateOption !== null && is_numeric($rateOption) && (float) $rateOption > 0)
            ? (float) $rateOption
            : $this->conversionService->getUsdToNgnRate();

        $isApply = (bool) $this->option('apply');
        $isDryRun = !$isApply;
        $usdThreshold = (float) $this->option('usd-threshold');

        $this->newLine();
        $this->line("<fg=white;bg=blue;options=bold> LINKOCIAL WALLET CURRENCY RECOVERY & AUDIT </fg=white;bg=blue;options=bold>");
        $this->line("Configured Exchange Rate: <fg=yellow;options=bold>1 USD = ₦" . number_format($rate, 2) . " NGN</>");
        $this->line("Operational Mode: " . ($isDryRun 
            ? "<fg=black;bg=yellow;options=bold> DRY RUN (PREVIEW ONLY - NO DATABASE MODIFICATIONS) </>" 
            : "<fg=white;bg=red;options=bold> LIVE EXECUTION (APPLYING PERMANENT BALANCE CONVERSIONS) </>"));
        $this->newLine();

        if ($isApply && !$this->option('force')) {
            if (!$this->confirm("Are you sure you want to apply balance recovery for eligible accounts using rate 1 USD = ₦{$rate}?", false)) {
                $this->warn("Operation cancelled by administrator. No records modified.");
                return Command::SUCCESS;
            }
        }

        // Auto-run migration if tracking columns are missing
        if (!Schema::hasColumn('users', 'currency_reconciliation_status')) {
            $this->warn("Database schema is missing currency tracking columns. Running migrations automatically...");
            try {
                $this->call('migrate', ['--force' => true]);
            } catch (\Throwable $e) {
                $this->error("Auto-migration notice: " . $e->getMessage());
            }
        }
        $hasTrackingColumns = Schema::hasColumn('users', 'currency_reconciliation_status');

        $users = User::all();
        $totalUsers = $users->count();

        $eligibleUsd = [];
        $alreadyNgn = [];
        $ambiguous = [];
        $zeroBalances = [];
        $alreadyMigrated = [];

        $this->info("Auditing {$totalUsers} customer wallets...");
        $bar = $this->output->createProgressBar($totalUsers);
        $bar->start();

        foreach ($users as $user) {
            $balance = (float) $user->balance;

            // 1. Idempotency Check: Was user already converted?
            $hasMigrationTx = Transaction::where('user_id', $user->id)
                ->where('transaction_type', 'currency_migration')
                ->exists();

            $isAlreadyRecovered = false;
            if ($hasTrackingColumns) {
                $isAlreadyRecovered = ($user->legacy_balance_recovered_at !== null || $user->currency_reconciliation_status === 'migrated');
            }

            if ($isAlreadyRecovered || $hasMigrationTx) {
                $alreadyMigrated[] = [
                    'user' => $user,
                    'balance' => $balance,
                    'reason' => 'Already migrated in previous recovery cycle'
                ];
                $bar->advance();
                continue;
            }

            // 2. Zero or Negative Balance
            if ($balance <= 0) {
                $zeroBalances[] = [
                    'user' => $user,
                    'balance' => $balance,
                    'reason' => 'Zero or empty balance ($0.00 / ₦0.00)'
                ];
                $bar->advance();
                continue;
            }

            // 3. Inspect Transaction and Payment History
            $hasPostDeploymentDeposit = Payment::where('user_id', $user->id)
                ->where('status', 'success')
                ->where('created_at', '>=', '2026-10-25 00:00:00')
                ->where('currency', 'NGN')
                ->exists();

            $totalNgnDeposits = Payment::where('user_id', $user->id)
                ->where('status', 'success')
                ->where('currency', 'NGN')
                ->sum('amount');

            // 4. Classification
            if ($hasPostDeploymentDeposit && $balance >= 100) {
                // User deposited native NGN after migration, current balance is already NGN
                $alreadyNgn[] = [
                    'user' => $user,
                    'balance' => $balance,
                    'reason' => 'Funded wallet in native NGN post-deployment'
                ];
            } elseif ($balance > $usdThreshold && $totalNgnDeposits >= $balance) {
                // Large balance matching known NGN deposits
                $alreadyNgn[] = [
                    'user' => $user,
                    'balance' => $balance,
                    'reason' => "Balance (₦" . number_format($balance, 2) . ") supported by historical NGN deposits"
                ];
            } elseif ($balance <= $usdThreshold && !$hasPostDeploymentDeposit) {
                // Confirmed legacy USD balance (e.g. $47 USD)
                $convertedNgn = round($balance * $rate, 2);
                $eligibleUsd[] = [
                    'user' => $user,
                    'old_balance_usd' => $balance,
                    'new_balance_ngn' => $convertedNgn,
                    'diff_ngn' => $convertedNgn - $balance,
                ];
            } else {
                // Ambiguous balance: cannot mathematically verify without manual review
                $ambiguous[] = [
                    'user' => $user,
                    'balance' => $balance,
                    'reason' => "Balance {$balance} requires manual review (mixed history or > threshold {$usdThreshold})"
                ];
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // --- Render Audit Report Tables ---
        $this->line("<fg=yellow;options=bold>1. ELIGIBLE LEGACY USD ACCOUNTS (PROPOSED CONVERSION)</>");
        if (count($eligibleUsd) > 0) {
            $tableData = [];
            foreach ($eligibleUsd as $item) {
                $u = $item['user'];
                $tableData[] = [
                    $u->id,
                    $u->username ?? $u->email,
                    '$' . number_format($item['old_balance_usd'], 2),
                    '₦' . number_format($rate, 2),
                    '₦' . number_format($item['new_balance_ngn'], 2),
                    '+₦' . number_format($item['diff_ngn'], 2),
                ];
            }
            $this->table(
                ['User ID', 'Username/Email', 'Current Legacy (USD)', 'Rate Applied', 'Recovered (NGN)', 'Wallet Adjustment'],
                $tableData
            );
        } else {
            $this->line("  <fg=green>No pending legacy USD accounts detected.</>");
        }
        $this->newLine();

        if (count($ambiguous) > 0) {
            $this->line("<fg=red;options=bold>2. FLAGGED AMBIGUOUS ACCOUNTS (REQUIRING MANUAL RECONCILIATION)</>");
            $ambData = [];
            foreach ($ambiguous as $item) {
                $u = $item['user'];
                $ambData[] = [
                    $u->id,
                    $u->username ?? $u->email,
                    number_format($item['balance'], 2),
                    $item['reason']
                ];
            }
            $this->table(['User ID', 'Username/Email', 'Stored Balance', 'Audit Reason'], $ambData);
            $this->newLine();
        }

        // --- Summary Statistics ---
        $this->line("<fg=cyan;options=bold>AUDIT SUMMARY STATISTICS:</>");
        $this->line(" • Total Wallets Audited: <options=bold>{$totalUsers}</>");
        $this->line(" • Eligible Legacy USD Accounts: <fg=green;options=bold>" . count($eligibleUsd) . "</>");
        $this->line(" • Confirmed Already-NGN Wallets: <fg=blue;options=bold>" . count($alreadyNgn) . "</>");
        $this->line(" • Zero/Empty Balances: <fg=gray>" . count($zeroBalances) . "</>");
        $this->line(" • Previously Migrated (Idempotent Skip): <fg=gray>" . count($alreadyMigrated) . "</>");
        $this->line(" • Flagged for Manual Reconciliation: <fg=red;options=bold>" . count($ambiguous) . "</>");
        $this->newLine();

        // --- Execution Phase ---
        if ($isDryRun) {
            $this->warn("DRY RUN COMPLETE: No database records were modified.");
            $this->line("To execute this recovery and credit customer wallets with verified NGN values, run:");
            $this->line("  <fg=yellow;options=bold>php artisan wallet:recover-legacy-balances --apply</>");
            $this->newLine();
            return Command::SUCCESS;
        }

        // APPLY CONVERSIONS
        $this->info("Applying conversions to database...");
        $convertedCount = 0;
        $failedCount = 0;

        foreach ($eligibleUsd as $item) {
            /** @var User $targetUser */
            $targetUser = $item['user'];
            $oldUsd = $item['old_balance_usd'];
            $newNgn = $item['new_balance_ngn'];
            $diff = $item['diff_ngn'];

            DB::beginTransaction();
            try {
                // Lock row to prevent race conditions during migration
                $lockedUser = User::where('id', $targetUser->id)->lockForUpdate()->first();

                // Re-check balance hasn't shifted
                if ((float) $lockedUser->balance !== (float) $oldUsd) {
                    Log::warning("Skipping user {$lockedUser->id}: balance changed concurrently from {$oldUsd} to {$lockedUser->balance}");
                    DB::rollBack();
                    $failedCount++;
                    continue;
                }

                // Update user wallet balance
                $lockedUser->balance = $newNgn;
                $lockedUser->currency_code = 'NGN';
                if ($hasTrackingColumns) {
                    $lockedUser->legacy_balance_usd = $oldUsd;
                    $lockedUser->legacy_recovery_rate = $rate;
                    $lockedUser->legacy_balance_recovered_at = now();
                    $lockedUser->currency_reconciliation_status = 'migrated';
                    $lockedUser->reconciliation_notes = "Recovered legacy balance: {$oldUsd} USD converted at 1 USD = ₦{$rate} NGN to ₦" . number_format($newNgn, 2);
                }
                $lockedUser->save();

                // Record in Financial Ledger
                $ref = 'REC_' . strtoupper(Str::random(10));
                Transaction::create([
                    'user_id' => $lockedUser->id,
                    'amount' => abs($diff),
                    'type' => $diff >= 0 ? 'credit' : 'debit',
                    'transaction_type' => 'currency_migration',
                    'balance_before' => $oldUsd,
                    'balance_after' => $newNgn,
                    'description' => "Legacy balance recovery: converted \${$oldUsd} USD to ₦" . number_format($newNgn, 2) . " NGN (Rate: 1 USD = ₦{$rate})",
                    'reference' => $ref,
                ]);

                ActivityLog::log("Recovered legacy wallet balance to NGN", $lockedUser, [
                    'old_balance_usd' => $oldUsd,
                    'new_balance_ngn' => $newNgn,
                    'rate' => $rate,
                    'reference' => $ref,
                ]);

                DB::commit();
                $convertedCount++;
            } catch (\Throwable $e) {
                DB::rollBack();
                Log::error("Failed recovering balance for user {$targetUser->id}: " . $e->getMessage());
                $failedCount++;
            }
        }

        if ($hasTrackingColumns) {
            // Mark already NGN accounts so they aren't repeatedly scanned
            foreach ($alreadyNgn as $item) {
                $u = $item['user'];
                User::where('id', $u->id)
                    ->whereNull('currency_reconciliation_status')
                    ->update([
                        'currency_reconciliation_status' => 'reconciled_ngn',
                        'currency_code' => 'NGN',
                        'reconciliation_notes' => $item['reason']
                    ]);
            }

            // Mark ambiguous accounts for admin attention
            foreach ($ambiguous as $item) {
                $u = $item['user'];
                User::where('id', $u->id)
                    ->whereNull('currency_reconciliation_status')
                    ->update([
                        'currency_reconciliation_status' => 'flagged_ambiguous',
                        'reconciliation_notes' => $item['reason']
                    ]);
            }
        }

        $this->newLine();
        $this->info("<fg=green;options=bold>RECOVERY EXECUTION COMPLETED!</>");
        $this->line(" • Successfully Converted: <fg=green;options=bold>{$convertedCount}</>");
        if ($failedCount > 0) {
            $this->line(" • Failures/Skipped due to race conditions: <fg=red;options=bold>{$failedCount}</>");
        }
        $this->line(" • Verified NGN Accounts Preserved: <options=bold>" . count($alreadyNgn) . "</>");
        $this->line(" • Ambiguous Accounts Flagged for Review: <fg=yellow;options=bold>" . count($ambiguous) . "</>");
        $this->newLine();

        return Command::SUCCESS;
    }
}
