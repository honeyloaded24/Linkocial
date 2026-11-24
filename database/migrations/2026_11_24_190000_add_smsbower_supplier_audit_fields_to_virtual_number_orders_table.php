<?php

use Illuminate\Database\\Migrations\\Migration;
use Illuminate\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('virtual_number_orders')) {
            return;
        }

        Schema::table('virtual_number_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('virtual_number_orders', 'provider_supplier_id')) {
                $table->string('provider_supplier_id')->nullable();
            }
            if (!Schema::hasColumn('virtual_number_orders', 'provider_quote_usd')) {
                $table->decimal('provider_quote_usd', 12, 6)->nullable();
            }
            if (!Schema::hasColumn('virtual_number_orders', 'provider_returned_operator')) {
                $table->string('provider_returned_operator')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('virtual_number_orders')) {
            return;
        }

        Schema::table('virtual_number_orders', function (Blueprint $table) {
            foreach (['provider_supplier_id', 'provider_quote_usd', 'provider_returned_operator'] as $column) {
                if (Schema::hasColumn('virtual_number_orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
