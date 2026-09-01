<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_currency_id')->constrained('currencies')->onDelete('cascade');
            $table->foreignId('to_currency_id')->constrained('currencies')->onDelete('cascade');
            $table->decimal('rate', 10, 4);
            $table->date('rate_date');
            $table->timestamps();
            
            $table->index(['from_currency_id', 'to_currency_id', 'rate_date']);
            $table->unique(['from_currency_id', 'to_currency_id', 'rate_date'], 'idx_exchange_unique');
            $table->unique(['from_currency_id', 'to_currency_id', 'rate_date'],'uniq_exchange_rate_pair_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};