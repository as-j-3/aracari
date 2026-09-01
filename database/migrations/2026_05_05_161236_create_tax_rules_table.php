<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_id')->constrained('taxes')->onDelete('cascade');
            $table->foreignId('country_id')->nullable()->constrained('countries')->onDelete('cascade');
            $table->foreignId('provider_id')->nullable()->constrained('provider_services')->onDelete('cascade');
            $table->foreignId('service_type_id')->nullable()->constrained('type_of_services')->onDelete('set null');
            $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods')->onDelete('set null');
            $table->foreignId('trip_id')->nullable()->constrained('trips')->onDelete('cascade');
            $table->decimal('min_amount', 10, 2)->nullable();
            $table->decimal('max_amount', 10, 2)->nullable();
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->onDelete('set null');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by_user')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            
            $table->index(['country_id', 'is_active']);
            $table->index(['trip_id', 'is_active']);
            $table->index(['provider_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_rules');
    }
};