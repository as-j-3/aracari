<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trip_reservations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('booking_id')->constrained('bookings')->onDelete('cascade');
            $table->foreignId('trip_id')->constrained('trips')->onDelete('cascade');
            $table->enum('direction', ['outbound', 'return'])->nullable();
            $table->string('status')->default('active');
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index('booking_id');
            $table->index('trip_id');
            $table->index(['booking_id', 'trip_id']);

            $table->index(['trip_id', 'status'], 'idx_reservations_trip_status');
            $table->index(['status', 'trip_id'], 'idx_reservations_status_trip');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_reservations');
    }
};
