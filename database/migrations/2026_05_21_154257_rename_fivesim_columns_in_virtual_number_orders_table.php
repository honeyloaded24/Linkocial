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
        Schema::table('virtual_number_orders', function (Blueprint $table) {
            $table->renameColumn('fivesim_id', 'api_order_id');
            $table->renameColumn('fivesim_cost', 'provider_cost');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('virtual_number_orders', function (Blueprint $table) {
            $table->renameColumn('api_order_id', 'fivesim_id');
            $table->renameColumn('provider_cost', 'fivesim_cost');
        });
    }
};
