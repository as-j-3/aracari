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
        Schema::create('trip_waypoints', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
            $table->foreignId('city_id')->constrained();
            $table->foreignId('city_station_id')->nullable()->constrained();         
            $table->timestamps();

            $table->unique(['trip_id', 'city_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trip_waypoints');
    }
};