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
        // 1. Orders table composite indexes
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->index(['user_id', 'status'], 'idx_orders_user_status');
                $table->index(['user_id', 'created_at'], 'idx_orders_user_created');
            });
        }

        // 2. Virtual Number Orders indexes
        if (Schema::hasTable('virtual_number_orders')) {
            Schema::table('virtual_number_orders', function (Blueprint $table) {
                $table->index(['status', 'expires_at'], 'idx_vno_status_expires');
                $table->index(['user_id', 'status'], 'idx_vno_user_status');
            });
        }

        // 3. VTU Orders indexes
        if (Schema::hasTable('vtu_orders')) {
            Schema::table('vtu_orders', function (Blueprint $table) {
                $table->index('api_reference', 'idx_vtu_api_reference');
                $table->index('status', 'idx_vtu_status');
                $table->index(['user_id', 'status'], 'idx_vtu_user_status');
            });
        }

        // 4. Log Items inventory index
        if (Schema::hasTable('log_items')) {
            Schema::table('log_items', function (Blueprint $table) {
                $table->index(['log_product_id', 'is_sold'], 'idx_log_items_product_sold');
            });
        }

        // 5. Transactions ledger indexes
        if (Schema::hasTable('transactions')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->index(['user_id', 'created_at'], 'idx_txn_user_created');
                $table->index('transaction_type', 'idx_txn_type');
            });
        }

        // 6. Payments indexes
        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->index('status', 'idx_payments_status');
                $table->index(['user_id', 'status'], 'idx_payments_user_status');
            });
        }

        // 7. Services catalogue indexes
        if (Schema::hasTable('services')) {
            Schema::table('services', function (Blueprint $table) {
                $table->index('is_active', 'idx_services_is_active');
                $table->index(['is_active', 'category'], 'idx_services_active_category');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropIndex('idx_orders_user_status');
                $table->dropIndex('idx_orders_user_created');
            });
        }

        if (Schema::hasTable('virtual_number_orders')) {
            Schema::table('virtual_number_orders', function (Blueprint $table) {
                $table->dropIndex('idx_vno_status_expires');
                $table->dropIndex('idx_vno_user_status');
            });
        }

        if (Schema::hasTable('vtu_orders')) {
            Schema::table('vtu_orders', function (Blueprint $table) {
                $table->dropIndex('idx_vtu_api_reference');
                $table->dropIndex('idx_vtu_status');
                $table->dropIndex('idx_vtu_user_status');
            });
        }

        if (Schema::hasTable('log_items')) {
            Schema::table('log_items', function (Blueprint $table) {
                $table->dropIndex('idx_log_items_product_sold');
            });
        }

        if (Schema::hasTable('transactions')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->dropIndex('idx_txn_user_created');
                $table->dropIndex('idx_txn_type');
            });
        }

        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropIndex('idx_payments_status');
                $table->dropIndex('idx_payments_user_status');
            });
        }

        if (Schema::hasTable('services')) {
            Schema::table('services', function (Blueprint $table) {
                $table->dropIndex('idx_services_is_active');
                $table->dropIndex('idx_services_active_category');
            });
        }
    }
};
