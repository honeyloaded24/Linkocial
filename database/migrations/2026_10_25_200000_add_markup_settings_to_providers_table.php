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
        if (Schema::hasTable('providers')) {
            Schema::table('providers', function (Blueprint $table) {
                if (!Schema::hasColumn('providers', 'currency')) {
                    $table->string('currency', 10)->default('USD')->after('api_key');
                }
                if (!Schema::hasColumn('providers', 'pricing_mode')) {
                    $table->string('pricing_mode', 30)->default('percentage')->after('currency');
                }
                if (!Schema::hasColumn('providers', 'percentage_markup')) {
                    $table->decimal('percentage_markup', 8, 2)->default(30.00)->after('pricing_mode');
                }
                if (!Schema::hasColumn('providers', 'fixed_markup_per_k')) {
                    $table->decimal('fixed_markup_per_k', 12, 2)->default(0.00)->after('percentage_markup');
                }
                if (!Schema::hasColumn('providers', 'min_selling_price')) {
                    $table->decimal('min_selling_price', 12, 2)->nullable()->after('fixed_markup_per_k');
                }
                if (!Schema::hasColumn('providers', 'min_profit')) {
                    $table->decimal('min_profit', 12, 2)->nullable()->after('min_selling_price');
                }
                if (!Schema::hasColumn('providers', 'rounding_rule')) {
                    $table->string('rounding_rule', 30)->default('2_decimal')->after('min_profit');
                }
                if (!Schema::hasColumn('providers', 'last_synced_at')) {
                    $table->timestamp('last_synced_at')->nullable()->after('rounding_rule');
                }
                if (!Schema::hasColumn('providers', 'last_sync_status')) {
                    $table->string('last_sync_status', 50)->nullable()->after('last_synced_at');
                }
                if (!Schema::hasColumn('providers', 'last_sync_message')) {
                    $table->text('last_sync_message')->nullable()->after('last_sync_status');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('providers')) {
            Schema::table('providers', function (Blueprint $table) {
                $columns = [
                    'currency',
                    'pricing_mode',
                    'percentage_markup',
                    'fixed_markup_per_k',
                    'min_selling_price',
                    'min_profit',
                    'rounding_rule',
                    'last_synced_at',
                    'last_sync_status',
                    'last_sync_message',
                ];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('providers', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
