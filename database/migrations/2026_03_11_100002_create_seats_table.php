<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->onDelete('cascade');
            $table->string('seat_name', 10);
            $table->enum('status', ['available', 'booked', 'maintenance'])->default('available');
            $table->timestamps();
            
            $table->unique(['vehicle_id', 'seat_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seats');
    }
};
