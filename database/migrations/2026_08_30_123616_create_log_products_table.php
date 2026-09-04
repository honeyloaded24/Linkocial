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
        Schema::create('log_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('log_category_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('api_product_id')->unique();
            $table->string('api_product_key')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('base_api_price', 10, 2);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('our_price', 10, 2)->default(0); // Custom price set by admin
            $table->integer('in_stock')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('log_products');
    }
};
