<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_direct_payment_infos', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('direct_transfer_payment_id')
                ->constrained('direct_transfer_payments')
                ->restrictOnDelete();

            $table->foreignId('booking_payment_id')
                ->constrained('booking_payments')
                ->restrictOnDelete();

            $table->string('receipt_number')->nullable();
            $table->string('sender_name');

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Indexes
            |--------------------------------------------------------------------------
            */

            $table->index('direct_transfer_payment_id');
            $table->index('booking_payment_id');
            $table->index('receipt_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_direct_payment_infos');
    }
};