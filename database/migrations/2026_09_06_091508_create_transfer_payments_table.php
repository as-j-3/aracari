<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfer_payments', function (Blueprint $table) {
            $table->id('transfer_payment_id'); // Primary Key
            
            // المفتاح الأجنبي المرتبط بطريقة الدفع
           $table->foreignId('payment_method_id')
          ->constrained('payment_methods')
          ->cascadeOnDelete();
            $table->string('name');
            $table->string('phone_number')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfer_payments');
    }
};