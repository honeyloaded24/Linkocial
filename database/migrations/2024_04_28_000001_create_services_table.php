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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., 'Instagram Likes'
            $table->text('description')->nullable();
            
            // This is the base selling price to the user (can be dynamic based on the active provider's markup)
            $table->decimal('selling_price', 10, 4);   
            
            $table->string('type')->default('Default'); // Default, Custom Comments, etc.
            $table->integer('min')->default(0);
            $table->integer('max')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
