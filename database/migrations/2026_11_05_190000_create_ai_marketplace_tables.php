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
        // 1. AI Products catalog
        if (!Schema::hasTable('ai_products')) {
            Schema::create('ai_products', function (Blueprint $table) {
                $table->id();
                $table->string('provider_product_id')->unique();
                $table->string('name');
                $table->string('category')->default('Google AI')->index();
                $table->text('description')->nullable();
                $table->string('icon')->nullable();
                $table->unsignedBigInteger('wholesale_price_kobo')->default(0);
                $table->decimal('wholesale_price', 12, 2)->default(0.00);
                $table->decimal('retail_price', 12, 2)->default(0.00);
                $table->boolean('in_stock')->default(true);
                $table->integer('stock_count')->default(999);
                $table->string('delivery_type')->default('automatic'); // automatic | manual
                $table->boolean('is_active')->default(true)->index();
                $table->boolean('is_hot')->default(false);
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        // 2. AI Orders
        if (!Schema::hasTable('ai_orders')) {
            Schema::create('ai_orders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('ai_product_id')->nullable()->constrained('ai_products')->nullOnDelete();
                $table->string('provider_order_id')->nullable()->index();
                $table->string('idempotency_key')->unique();
                $table->string('product_name');
                $table->integer('quantity')->default(1);
                $table->decimal('unit_price', 12, 2);
                $table->decimal('total_price', 12, 2);
                $table->decimal('provider_cost', 12, 2)->default(0.00);
                $table->string('status')->default('pending')->index(); // pending, processing, awaiting_manual, fulfilled, cancelled, refunded
                $table->string('delivery_mode')->default('portal_only');
                $table->longText('delivery_payload')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        // 3. AI Webhook Events (for durable deduplication)
        if (!Schema::hasTable('ai_webhook_events')) {
            Schema::create('ai_webhook_events', function (Blueprint $table) {
                $table->id();
                $table->string('event_id')->unique();
                $table->string('event_type')->index();
                $table->json('payload');
                $table->timestamp('processed_at')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_webhook_events');
        Schema::dropIfExists('ai_orders');
        Schema::dropIfExists('ai_products');
    }
};
