<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_taxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->onDelete('cascade');
            $table->foreignId('tax_id')->constrained('taxes')->onDelete('cascade');
            $table->string('tax_name', 100); 
            $table->enum('tax_type_rate_or_amount', ['percentage', 'fixed']);
            $table->decimal('tax_rate', 5, 2)->nullable();
            $table->decimal('tax_amount', 10, 2);
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->onDelete('set null');
            $table->decimal('exchange_rate', 10, 4)->nullable();
            $table->decimal('base_tax_amount', 10, 2)->nullable();
            $table->timestamps();
            
            $table->index('booking_id');
            $table->index('tax_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_taxes');
    }
};
