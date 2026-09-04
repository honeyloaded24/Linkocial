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
        // 1. Update log_products table
        Schema::table('log_products', function (Blueprint $table) {
            $table->string('type')->default('api')->after('id'); // 'api' or 'local'
        });

        Schema::table('log_products', function (Blueprint $table) {
            $table->integer('api_product_id')->nullable()->change();
            $table->string('api_product_key')->nullable()->change();
        });
        
        try {
            Schema::table('log_products', function (Blueprint $table) {
                $table->dropUnique(['api_product_id']);
                $table->dropUnique(['api_product_key']);
            });
        } catch (\Exception $e) {
            // Ignore if index doesn't exist
        }

        // 2. Update log_orders table
        Schema::table('log_orders', function (Blueprint $table) {
            $table->string('api_product_key')->nullable()->change();
        });

        // 3. Create log_items table
        Schema::create('log_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('log_product_id')->constrained()->cascadeOnDelete();
            $table->text('content');
            $table->boolean('is_sold')->default(false);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('log_order_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('log_items');

        Schema::table('log_orders', function (Blueprint $table) {
            $table->string('api_product_key')->nullable(false)->change();
        });

        Schema::table('log_products', function (Blueprint $table) {
            $table->dropColumn('type');
            $table->integer('api_product_id')->nullable(false)->change();
            $table->string('api_product_key')->nullable(false)->change();
        });
    }
};
