<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name')->nullable();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('vehicle_type_id')->constrained('vehicle_types')->onDelete('restrict');
            $table->string('model', 255);
            $table->string('outside_photo', 500)->nullable();
            $table->string('inside_photo', 500)->nullable();
            $table->tinyInteger('seats_count')->unsigned();
            $table->string('status')->default('active');
            $table->timestamps();
            
            // Performance indexes
            $table->index(['user_id', 'status']);
            $table->index('vehicle_type_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
