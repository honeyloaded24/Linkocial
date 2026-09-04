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
        Schema::create('log_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('log_product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('api_product_key'); // store in case product is deleted
            $table->string('product_name');
            $table->integer('quantity');
            $table->decimal('unit_cost', 10, 2);
            $table->decimal('total_cost', 10, 2);
            $table->string('external_order_id')->unique();
            $table->string('status')->default('completed'); // completed, failed
            $table->longText('delivered_items')->nullable(); // Store the actual log details (JSON)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('log_orders');
    }
};
