<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('city_stations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('city_id')->constrained('cities')->onDelete('cascade');
            $table->string('name', 255);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            
            $table->unique(['city_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('city_stations');
    }
};