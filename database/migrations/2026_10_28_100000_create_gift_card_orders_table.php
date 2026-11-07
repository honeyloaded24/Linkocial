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
        if (!Schema::hasTable('gift_card_orders')) {
            Schema::create('gift_card_orders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('order_reference')->unique();
                $table->string('provider_reference')->nullable()->index();
                $table->string('provider')->default('prestmit')->index();
                $table->string('brand_code')->index();
                $table->string('brand_name');
                $table->string('country_code', 10)->default('US');
                $table->decimal('face_value', 12, 2);
                $table->string('face_value_currency', 10)->default('USD');
                $table->decimal('amount_ngn', 14, 2);
                $table->decimal('provider_cost', 14, 4)->nullable();
                $table->string('provider_currency', 10)->nullable();
                $table->string('status', 30)->default('pending')->index();
                $table->string('idempotency_key')->unique();
                $table->text('status_details')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamp('failed_at')->nullable();
                $table->timestamp('refunded_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'created_at']);
                $table->index(['status', 'created_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gift_card_orders');
    }
};
