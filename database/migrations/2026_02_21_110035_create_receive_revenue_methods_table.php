<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receive_revenue_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('digital_pocket_id')->constrained('digital_pockets')->onDelete('restrict');
            $table->foreignId('provider_id')->constrained('provider_services')->onDelete('cascade');
            $table->text('account_number');
            $table->string('account_name', 255);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receive_revenue_methods');
    }
};
