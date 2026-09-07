<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('country_taxes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tax_id')->constrained('taxes')->cascadeOnDelete();
            $table->foreignId('country_id')->constrained('countries')->cascadeOnDelete();

            $table->enum('type', ['percentage', 'fixed'])->default('fixed');
            $table->decimal('rate', 5, 2)->nullable(); // for percentage
            $table->decimal('amount', 8, 2)->nullable(); // for fixed amount

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('country_taxes');
    }
};