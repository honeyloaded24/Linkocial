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
        if (Schema::hasTable('ai_tool_orders')) {
            Schema::table('ai_tool_orders', function (Blueprint $table) {
                if (!Schema::hasColumn('ai_tool_orders', 'failure_reason')) {
                    $table->text('failure_reason')->nullable()->after('error_message');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('ai_tool_orders') && Schema::hasColumn('ai_tool_orders', 'failure_reason')) {
            Schema::table('ai_tool_orders', function (Blueprint $table) {
                $table->dropColumn('failure_reason');
            });
        }
    }
};
