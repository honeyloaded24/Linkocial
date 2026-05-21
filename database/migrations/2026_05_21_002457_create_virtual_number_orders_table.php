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
        Schema::create('virtual_number_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('fivesim_id')->unique();
            $table->string('phone_number');
            $table->string('country');
            $table->string('operator');
            $table->string('product');
            $table->decimal('cost', 10, 2); // Platform cost deducted from user wallet
            $table->decimal('fivesim_cost', 10, 4); // Actual cost in 5sim base currency
            $table->string('status')->default('pending'); // pending, received, canceled, finished
            $table->text('sms_text')->nullable();
            $table->string('sms_code')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('virtual_number_orders');
    }
};
