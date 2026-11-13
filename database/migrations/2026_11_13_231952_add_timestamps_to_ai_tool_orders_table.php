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
        Schema::table('ai_tool_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('ai_tool_orders', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('last_polled_at');
            }
            if (!Schema::hasColumn('ai_tool_orders', 'refunded_at')) {
                $table->timestamp('refunded_at')->nullable()->after('completed_at');
            }
            if (!Schema::hasColumn('ai_tool_orders', 'max_total_usdt')) {
                $table->decimal('max_total_usdt', 12, 4)->nullable()->after('profit_ngn');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_tool_orders', function (Blueprint $table) {
            $table->dropColumn(['completed_at', 'refunded_at', 'max_total_usdt']);
        });
    }
};
