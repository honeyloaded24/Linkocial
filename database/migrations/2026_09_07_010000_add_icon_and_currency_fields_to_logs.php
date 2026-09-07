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
        if (Schema::hasTable('log_products') && !Schema::hasColumn('log_products', 'icon')) {
            Schema::table('log_products', function (Blueprint $table) {
                $table->string('icon')->nullable()->after('description');
            });
        }

        if (Schema::hasTable('log_orders') && !Schema::hasColumn('log_orders', 'amount_usd')) {
            Schema::table('log_orders', function (Blueprint $table) {
                $table->decimal('amount_usd', 10, 4)->nullable()->after('total_cost');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('log_products') && Schema::hasColumn('log_products', 'icon')) {
            Schema::table('log_products', function (Blueprint $table) {
                $table->dropColumn('icon');
            });
        }

        if (Schema::hasTable('log_orders') && Schema::hasColumn('log_orders', 'amount_usd')) {
            Schema::table('log_orders', function (Blueprint $table) {
                $table->dropColumn('amount_usd');
            });
        }
    }
};
