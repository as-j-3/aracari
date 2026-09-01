<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_payments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('payment_method_id')->constrained('payment_methods')->onDelete('restrict');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->unsignedBigInteger('booking_id')->nullable();
            $table->decimal('payed_amount', 10, 2);
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->onDelete('set null');
            $table->decimal('exchange_rate', 10, 4)->nullable();
            $table->decimal('base_amount', 10, 2)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            
            $table->index(['booking_id', 'payment_method_id']);
            $table->index(['user_id', 'created_at']);
            $table->index('payed_amount');
        });
    }

    public function down(): void
    {
Schema::dropIfExists('booking_payments');
    }
};
