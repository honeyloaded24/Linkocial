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
        if (Schema::hasTable('virtual_number_orders') && !Schema::hasColumn('virtual_number_orders', 'server')) {
            Schema::table('virtual_number_orders', function (Blueprint $table) {
                $table->string('server')->default('server1')->after('operator');
                $table->index(['server', 'status']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('virtual_number_orders') && Schema::hasColumn('virtual_number_orders', 'server')) {
            Schema::table('virtual_number_orders', function (Blueprint $table) {
                $table->dropIndex(['server', 'status']);
                $table->dropColumn('server');
            });
        }
    }
};
