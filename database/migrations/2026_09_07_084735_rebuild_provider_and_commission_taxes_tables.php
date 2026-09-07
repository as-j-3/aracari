<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        // 1. إزالة الجدولين القديمين إن وجدا
        Schema::dropIfExists('provider_taxes');
        Schema::dropIfExists('commission_taxes');

        Schema::enableForeignKeyConstraints();

        // 2. إنشاء provider_taxes بالربط الجديد
        Schema::create('provider_taxes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('country_tax_id')
                ->constrained('country_taxes')
                ->cascadeOnDelete();

            $table->foreignId('service_id')
                ->nullable()
                ->constrained('services')
                ->nullOnDelete();

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 3. إنشاء commission_taxes بالربط الجديد
        Schema::create('commission_taxes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('country_tax_id')
                ->constrained('country_taxes')
                ->cascadeOnDelete();

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
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('provider_taxes');
        Schema::dropIfExists('commission_taxes');
        Schema::enableForeignKeyConstraints();
    }
};