<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('ai_tool_order_items')) {
            $driver = DB::getDriverName();
            if ($driver === 'mysql') {
                DB::statement('ALTER TABLE ai_tool_order_items MODIFY COLUMN code LONGTEXT NOT NULL');
                DB::statement('ALTER TABLE ai_tool_order_items MODIFY COLUMN details LONGTEXT NULL');
            } elseif ($driver === 'pgsql') {
                DB::statement('ALTER TABLE ai_tool_order_items ALTER COLUMN code TYPE TEXT');
                DB::statement('ALTER TABLE ai_tool_order_items ALTER COLUMN details TYPE TEXT');
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
