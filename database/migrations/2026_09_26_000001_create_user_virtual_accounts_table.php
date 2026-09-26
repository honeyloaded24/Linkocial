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
        Schema::create('user_virtual_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('bank_id', 30); // 9PSB, PALMPAY
            $table->string('bank_name', 100); // 9Payment Service Bank, PalmPay
            $table->string('account_number', 30)->index();
            $table->string('account_name', 150);
            $table->string('reference', 100)->nullable()->index();
            $table->string('bvn', 20)->nullable();
            $table->string('status', 20)->default('active'); // active, inactive
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'bank_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_virtual_accounts');
    }
};
