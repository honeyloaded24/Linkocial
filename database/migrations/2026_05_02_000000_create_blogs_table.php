<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blogs', function (Blueprint $row) {
            $row->id();
            $row->string('title');
            $row->string('slug')->unique();
            $row->string('category')->default('Growth');
            $row->string('image')->nullable();
            $row->text('excerpt')->nullable();
            $row->longText('content');
            $row->string('status')->default('published'); // published, draft
            $row->string('meta_title')->nullable();
            $row->string('meta_description')->nullable();
            $row->unsignedBigInteger('views')->default(0);
            $row->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blogs');
    }
};
