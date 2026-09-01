<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_services', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->unique()->constrained('users')->onDelete('cascade');
            $table->foreignId('type_of_service_id')->constrained('type_of_services')->onDelete('restrict');
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('cr_number', 100)->unique();
            $table->string('brand_photo', 500)->nullable();
            $table->string('born_country', 100)->nullable();
            $table->string('contact_phone', 20)->nullable();
            $table->string('contact_email', 255)->nullable();
            $table->string('website', 255)->nullable();
            $table->string('status', 50)->default('pending');
            $table->enum('commission_type', ['service', 'booking'])->default('service');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_services');
    }
};
