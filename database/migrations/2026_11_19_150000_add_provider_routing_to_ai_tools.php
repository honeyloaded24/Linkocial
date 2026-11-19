<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('ai_tool_products') && !Schema::hasColumn('ai_tool_products', 'provider')) {
            Schema::table('ai_tool_products', function (Blueprint $table) {
                $table->string('provider', 30)->default('alaric')->index();
            });
        }

        if (Schema::hasTable('ai_tool_orders') && !Schema::hasColumn('ai_tool_orders', 'provider')) {
            Schema::table('ai_tool_orders', function (Blueprint $table) {
                $table->string('provider', 30)->default('alaric')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ai_tool_orders') && Schema::hasColumn('ai_tool_orders', 'provider')) {
            Schema::table('ai_tool_orders', fn(Blueprint $table) => $table->dropColumn('provider'));
        }
        if (Schema::hasTable('ai_tool_products') && Schema::hasColumn('ai_tool_products', 'provider')) {
            Schema::table('ai_tool_products', fn(Blueprint $table) => $table->dropColumn('provider'));
        }
    }
};
