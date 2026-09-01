<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commissions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->onDelete('set null');
            $table->enum('commission_type', ['fixed', 'percentage'])->default('fixed');
            $table->string('calculation_level', 50)->default('booking');
            $table->text('description')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commissions');
    }
};
