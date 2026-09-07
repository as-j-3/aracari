<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_taxes', function (Blueprint $table) {
            $table->id();

            // الربط بجدول country_taxes الوسيط
            $table->foreignId('country_tax_id')
                ->constrained('country_taxes')
                ->cascadeOnDelete();

            // الربط بـ service_id بحسب المخطط
            $table->foreignId('service_id')
                ->nullable()
                ->constrained('services')
                ->nullOnDelete();

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_taxes');
    }
};