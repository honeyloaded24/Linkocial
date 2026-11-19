<?php

namespace Tests\Feature;

use App\Models\AffiliateTransaction;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class AffiliateLedgerReconciliationTest extends TestCase
{
    public function test_apply_reconciliation_recomputes_totals_from_ledger(): void
    {
        $user = User::factory()->create([
            'commission_balance' => 999.00,
            'total_earned_commission' => 999.00,
            'total_withdrawn_commission' => 0.00,
        ]);

        AffiliateTransaction::create([
            'user_id' => $user->id,
            'amount' => 50.00,
            'type' => 'credit',
            'description' => 'Test commission',
            'balance_before' => 0.00,
            'balance_after' => 50.00,
        ]);
        AffiliateTransaction::create([
            'user_id' => $user->id,
            'amount' => 10.00,
            'type' => 'debit',
            'description' => 'Test withdrawal',
            'balance_before' => 50.00,
            'balance_after' => 40.00,
        ]);

        Artisan::call('affiliate:reconcile-ledger', ['--apply' => true]);

        $user->refresh();
        $this->assertEquals(40.00, (float) $user->commission_balance);
        $this->assertEquals(50.00, (float) $user->total_earned_commission);
        $this->assertEquals(10.00, (float) $user->total_withdrawn_commission);
    }
}
