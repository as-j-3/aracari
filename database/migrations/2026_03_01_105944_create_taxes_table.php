<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taxes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->enum('type', ['percentage', 'fixed'])->default('fixed');
            $table->decimal('rate', 5, 2)->nullable(); // for percentage
            $table->decimal('amount', 8, 2)->nullable();
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->onDelete('set null');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taxes');
    }
};