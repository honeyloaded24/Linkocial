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
        Schema::create('provider_service_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->onDelete('cascade');
            $table->foreignId('provider_id')->constrained('providers')->onDelete('cascade');
            $table->string('provider_service_id');
            
            // The cost from the provider
            $table->decimal('original_price', 10, 4); 
            
            // Failover Priority: 1 = Primary, 2 = Secondary, etc.
            $table->integer('priority')->default(1); 
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Ensure we don't map the same provider and service combo multiple times
            $table->unique(['service_id', 'provider_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provider_service_mappings');
    }
};
