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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            // Assuming there's a users table for the reseller client
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            
            $table->foreignId('service_id')->constrained('services');
            $table->foreignId('provider_id')->nullable()->constrained('providers'); // The provider ultimately used
            
            $table->string('provider_order_id')->nullable(); // ID returned by the provider
            
            $table->string('link');
            $table->integer('quantity');
            
            $table->decimal('charge', 10, 4); // What we charged the user
            $table->decimal('provider_cost', 10, 4)->nullable(); // What the provider charged us
            
            // Statuses: pending, in_progress, completed, partial, canceled, failed
            $table->string('status')->default('pending'); 
            
            $table->text('error_log')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
