<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained('provider_services')->onDelete('cascade');
            $table->foreignId('commission_id')->constrained('commissions')->onDelete('cascade');
            $table->decimal('min_amount', 10, 2)->nullable();
            $table->decimal('max_amount', 10, 2)->nullable();
            $table->decimal('value', 10, 2);
            $table->timestamps();
            $table->softDeletes();

            $table->index('provider_id');
            $table->index('commission_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_commissions');
    }
};
