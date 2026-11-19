<?php

namespace App\Console\Commands;

use App\Models\AffiliateTransaction;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReconcileAffiliateLedger extends Command
{
    protected $signature = 'affiliate:reconcile-ledger {--apply : Repair affiliate balance and totals from ledger entries}';
    protected $description = 'Audit affiliate commission balances and totals against AffiliateTransaction ledger entries';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $this->info($apply ? 'LIVE RECONCILIATION: ledger values will be applied.' : 'DRY RUN: no records will be modified.');

        $mismatches = 0;
        User::where(function ($q) {
            $q->where('commission_balance', '!=', 0)
              ->orWhere('total_earned_commission', '!=', 0)
              ->orWhere('total_withdrawn_commission', '!=', 0)
              ->orWhereExists(function ($sub) {
                  $sub->select(DB::raw(1))->from('affiliate_transactions')
                      ->whereColumn('affiliate_transactions.user_id', 'users.id');
              });
        })->chunkById(100, function ($users) use ($apply, &$mismatches) {
            foreach ($users as $user) {
                $ledger = AffiliateTransaction::where('user_id', $user->id)
                    ->selectRaw("SUM(CASE WHEN type = 'credit' THEN amount ELSE 0 END) as earned")
                    ->selectRaw("SUM(CASE WHEN type = 'debit' THEN amount ELSE 0 END) as withdrawn")
                    ->selectRaw("SUM(CASE WHEN type = 'credit' THEN amount ELSE -amount END) as balance")
                    ->first();

                $earned = round((float) ($ledger->earned ?? 0), 2);
                $withdrawn = round((float) ($ledger->withdrawn ?? 0), 2);
                $balance = round((float) ($ledger->balance ?? 0), 2);
                $current = [
                    'commission_balance' => round((float) $user->commission_balance, 2),
                    'total_earned_commission' => round((float) $user->total_earned_commission, 2),
                    'total_withdrawn_commission' => round((float) $user->total_withdrawn_commission, 2),
                ];
                $expected = [
                    'commission_balance' => $balance,
                    'total_earned_commission' => $earned,
                    'total_withdrawn_commission' => $withdrawn,
                ];

                if ($current === $expected) {
                    return;
                }

                $mismatches++;
                $this->line("User #{$user->id}: " . json_encode(['current' => $current, 'ledger' => $expected]));

                if ($apply) {
                    DB::transaction(function () use ($user, $expected) {
                        $locked = User::where('id', $user->id)->lockForUpdate()->firstOrFail();
                        $locked->commission_balance = $expected['commission_balance'];
                        $locked->total_earned_commission = $expected['total_earned_commission'];
                        $locked->total_withdrawn_commission = $expected['total_withdrawn_commission'];
                        $locked->save();
                    });
                }
            }
        });

        $this->info("Affiliate ledger mismatches: {$mismatches}");
        if (!$apply) {
            $this->line('To apply ledger-derived corrections, rerun with --apply after reviewing this report.');
        }

        return Command::SUCCESS;
    }
}
