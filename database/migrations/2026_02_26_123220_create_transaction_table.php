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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('transaction_reference_id');
            $table->foreignId('account_id')->constrained('accounts')->onDelete('cascade');
            $table->enum('transaction_type', ['debit', 'credit']);
            $table->decimal('amount', 15, 2);
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->onDelete('set null');
            $table->decimal('exchange_rate', 10, 4)->nullable();
            $table->decimal('base_amount', 15, 2)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->foreign('transaction_reference_id')->references('id')->on('transaction_references')
                ->onDelete('cascade')->onUpdate('cascade');
                
            $table->index(['transaction_reference_id', 'transaction_type']);
            $table->index(['account_id', 'created_at']);
            
            // Additional performance indexes
            $table->index(['transaction_type', 'created_at']);
            $table->index('amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
