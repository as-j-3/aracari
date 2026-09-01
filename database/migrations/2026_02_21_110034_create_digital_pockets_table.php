<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('digital_pockets', function (Blueprint $table) {
            $table->id();
            $table->string('pocket_name', 100);
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('digital_pockets');
    }
};
