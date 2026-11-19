<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReconcileWalletLedger extends Command
{
    protected $signature = 'wallet:reconcile-ledger {--apply : Create adjustment ledger entries and reconcile balances}';
    protected $description = 'Audit wallet balances against transaction ledger; dry-run by default';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $this->info($apply ? 'APPLY MODE: discrepancies will be corrected with audit entries.' : 'DRY RUN: no wallet changes will be made.');

        $duplicates = DB::table('transactions')
            ->whereNotNull('reference')
            ->select('reference')
            ->groupBy('reference')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($duplicates->isNotEmpty()) {
            $this->error('Duplicate transaction references detected: ' . $duplicates->count());
            foreach ($duplicates as $row) {
                $this->line('Duplicate reference: ' . $row->reference);
            }
        } else {
            $this->info('No duplicate transaction references found.');
        }

        $checked = 0;
        $mismatches = 0;
        User::chunkById(100, function ($users) use ($apply, &$checked, &$mismatches) {
            foreach ($users as $user) {
                $ledger = Transaction::where('user_id', $user->id)
                    ->selectRaw("SUM(CASE WHEN type = 'credit' THEN amount ELSE -amount END) as net")
                    ->first();
                $ledgerBalance = round((float) ($ledger->net ?? 0), 4);
                $walletBalance = round((float) $user->balance, 4);
                $checked++;

                if (abs($walletBalance - $ledgerBalance) < 0.0001) {
                    continue;
                }

                $difference = round($walletBalance - $ledgerBalance, 4);
                $mismatches++;
                $this->line("User #{$user->id}: wallet={$walletBalance}, ledger={$ledgerBalance}, difference={$difference}");

                if ($apply) {
                    DB::transaction(function () use ($user, $difference) {
                        $locked = User::where('id', $user->id)->lockForUpdate()->firstOrFail();
                        $freshLedger = Transaction::where('user_id', $locked->id)
                            ->selectRaw("SUM(CASE WHEN type = 'credit' THEN amount ELSE -amount END) as net")
                            ->first();
                        $currentLedger = round((float) ($freshLedger->net ?? 0), 4);
                        $currentWallet = round((float) $locked->balance, 4);
                        $adjustment = round($currentWallet - $currentLedger, 4);

                        if (abs($adjustment) < 0.0001) {
                            return;
                        }

                        Transaction::create([
                            'user_id' => $locked->id,
                            'amount' => abs($adjustment),
                            'type' => $adjustment > 0 ? 'credit' : 'debit',
                            'transaction_type' => 'ledger_reconciliation',
                            'balance_before' => $currentLedger,
                            'balance_after' => round($currentLedger + $adjustment, 4),
                            'description' => 'Wallet ledger reconciliation adjustment',
                            'reference' => 'WALLETREC_' . strtoupper(Str::uuid()->toString()),
                        ]);
                    });
                }
            }
        });

        $this->newLine();
        $this->info("Wallets checked: {$checked}");
        $this->info("Balance/ledger mismatches: {$mismatches}");
        if (!$apply) {
            $this->warn('Dry run only. Review findings before running with --apply.');
        }

        return Command::SUCCESS;
    }
}
