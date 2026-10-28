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
        if (Schema::hasTable('log_products') && !Schema::hasColumn('log_products', 'is_hot')) {
            Schema::table('log_products', function (Blueprint $table) {
                $table->boolean('is_hot')->default(false)->after('is_active')->index();
                $table->integer('hot_order')->default(0)->after('is_hot');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('log_products') && Schema::hasColumn('log_products', 'is_hot')) {
            Schema::table('log_products', function (Blueprint $table) {
                $table->dropColumn(['is_hot', 'hot_order']);
            });
        }
    }
};
