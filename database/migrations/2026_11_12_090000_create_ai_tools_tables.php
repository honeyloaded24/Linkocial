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
        // 1. AI Tool Categories
        if (!Schema::hasTable('ai_tool_categories')) {
            Schema::create('ai_tool_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->string('icon')->default('fa-solid fa-wand-magic-sparkles');
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
            });
        }

        // 2. AI Tool Products
        if (!Schema::hasTable('ai_tool_products')) {
            Schema::create('ai_tool_products', function (Blueprint $table) {
                $table->id();
                $table->string('provider_product_id')->unique()->index();
                $table->foreignId('category_id')->nullable()->constrained('ai_tool_categories')->nullOnDelete();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->decimal('price_usdt', 12, 4)->default(0.0000);
                $table->string('currency', 10)->default('USDT');
                $table->boolean('in_stock')->default(true);
                $table->integer('stock')->default(0);
                $table->integer('max_quantity')->default(20);
                $table->string('fulfillment', 30)->default('stock'); // 'stock' or 'on_demand'
                $table->json('price_tiers')->nullable();
                $table->boolean('is_active')->default(true);
                $table->boolean('is_featured')->default(false);
                $table->integer('sort_order')->default(0);
                $table->decimal('custom_markup_percentage', 8, 2)->nullable();
                $table->decimal('custom_markup_fixed', 12, 2)->nullable();
                $table->timestamp('last_synced_at')->nullable();
                $table->timestamps();

                $table->index(['is_active', 'in_stock']);
                $table->index('sort_order');
            });
        }

        // 3. AI Tool Orders
        if (!Schema::hasTable('ai_tool_orders')) {
            Schema::create('ai_tool_orders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('ai_tool_product_id')->nullable()->constrained('ai_tool_products')->nullOnDelete();
                $table->string('order_number', 50)->unique()->index();
                $table->string('provider_order_id')->nullable()->index();
                $table->string('provider_product_id');
                $table->string('product_name');
                $table->integer('quantity')->default(1);
                $table->decimal('unit_price_usdt', 12, 4)->default(0.0000);
                $table->decimal('total_price_usdt', 12, 4)->default(0.0000);
                $table->decimal('exchange_rate', 12, 4)->default(1500.0000);
                $table->decimal('provider_cost_ngn', 12, 4)->default(0.0000);
                $table->decimal('unit_price_ngn', 12, 2)->default(0.00);
                $table->decimal('total_paid_ngn', 12, 2)->default(0.00);
                $table->decimal('profit_ngn', 12, 2)->default(0.00);
                $table->string('idempotency_key', 64)->unique()->index();
                $table->string('status', 30)->default('pending')->index(); // pending, processing, completed, refunded, failed, under_review
                $table->string('provider_status', 30)->nullable(); // COMPLETED, PROCESSING, REFUNDED, FAILED, UNDER_REVIEW
                $table->json('provider_response')->nullable();
                $table->text('error_message')->nullable();
                $table->integer('retry_count')->default(0);
                $table->timestamp('last_polled_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'status']);
                $table->index('created_at');
            });
        }

        // 4. AI Tool Order Items (Delivered credentials/codes)
        if (!Schema::hasTable('ai_tool_order_items')) {
            Schema::create('ai_tool_order_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ai_tool_order_id')->constrained('ai_tool_orders')->cascadeOnDelete();
                $table->integer('item_index')->default(1);
                $table->text('code'); // Encrypted at rest via model cast
                $table->json('details')->nullable(); // Encrypted at rest via model cast
                $table->timestamps();

                $table->index('ai_tool_order_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_tool_order_items');
        Schema::dropIfExists('ai_tool_orders');
        Schema::dropIfExists('ai_tool_products');
        Schema::dropIfExists('ai_tool_categories');
    }
};
