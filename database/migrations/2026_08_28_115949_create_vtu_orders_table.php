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
        Schema::create('vtu_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('service_type'); // airtime, data, electricity, cable, exam_pin
            $table->string('network')->nullable(); // MTN, GLO, Airtel, 9Mobile, Ikeja, etc.
            $table->string('phone_or_meter'); // phone number or meter number
            $table->string('plan_or_amount')->nullable(); // data plan name or amount
            $table->decimal('cost', 10, 2); // how much was charged
            $table->string('status')->default('pending'); // pending, processing, completed, failed, refunded
            $table->string('api_reference')->nullable();
            $table->text('api_response')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vtu_orders');
    }
};
