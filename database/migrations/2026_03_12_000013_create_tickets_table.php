<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('trip_reservation_id')->constrained('trip_reservations')->onDelete('cascade');
            $table->foreignId('passenger_id')->constrained('passengers')->onDelete('cascade');
            $table->foreignId('seat_id')->nullable()->constrained('seats')->nullOnDelete();
            $table->string('ticket_number')->unique();
            $table->decimal('price_at_booking', 10, 2);
            $table->string('status')->default('active');
            $table->timestamp('boarded_at')->nullable();
            $table->unsignedBigInteger('boarded_by')->nullable();
            $table->decimal('cancellation_fee', 10, 2)->nullable();
            $table->decimal('refund_amount', 10, 2)->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index('trip_reservation_id');
            $table->index('passenger_id');
            $table->index('ticket_number');
            
            // Performance indexes
            $table->index(['trip_reservation_id', 'status']);
            $table->index(['passenger_id', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
