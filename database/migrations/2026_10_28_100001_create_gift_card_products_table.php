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
        if (!Schema::hasTable('gift_card_products')) {
            Schema::create('gift_card_products', function (Blueprint $table) {
                $table->id();
                $table->string('brand_code')->unique();
                $table->string('brand_name');
                $table->string('country_code', 10)->default('US');
                $table->string('currency', 10)->default('USD');
                $table->decimal('min_face_value', 12, 2)->default(0.00);
                $table->decimal('max_face_value', 12, 2)->default(0.00);
                $table->json('fixed_denominations')->nullable();
                $table->string('icon')->nullable();
                $table->boolean('is_active')->default(true);
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['is_active', 'country_code']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gift_card_products');
    }
};
