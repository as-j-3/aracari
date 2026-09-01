<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_types', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('type_name', 100);
            $table->timestamps();

            // Indexes
            $table->index('type_name');
            $table->index('uuid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_types');
    }
};
