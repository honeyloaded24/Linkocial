<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'legacy_balance_recovered_at')) {
                    $table->timestamp('legacy_balance_recovered_at')->nullable()->after('balance');
                }
                if (!Schema::hasColumn('users', 'legacy_balance_usd')) {
                    $table->decimal('legacy_balance_usd', 15, 4)->nullable()->after('legacy_balance_recovered_at');
                }
                if (!Schema::hasColumn('users', 'legacy_recovery_rate')) {
                    $table->decimal('legacy_recovery_rate', 15, 4)->nullable()->after('legacy_balance_usd');
                }
                if (!Schema::hasColumn('users', 'currency_reconciliation_status')) {
                    $table->string('currency_reconciliation_status', 50)->nullable()->default(null)->after('legacy_recovery_rate');
                }
                if (!Schema::hasColumn('users', 'reconciliation_notes')) {
                    $table->text('reconciliation_notes')->nullable()->after('currency_reconciliation_status');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $columns = [
                    'legacy_balance_recovered_at',
                    'legacy_balance_usd',
                    'legacy_recovery_rate',
                    'currency_reconciliation_status',
                    'reconciliation_notes',
                ];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('users', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
