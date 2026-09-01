<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repeated_trip_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repeat_id')->constrained('trip_repeats')->onDelete('cascade');
            $table->enum('day_of_week', ['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday']);
            $table->timestamps();

            $table->index('repeat_id');
            $table->unique(['repeat_id', 'day_of_week']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repeated_trip_days');
    }
};
