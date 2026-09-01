<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trip_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained('trips')->onDelete('cascade');
            $table->foreignId('policy_id')->constrained('policies')->onDelete('cascade');
            $table->timestamps();

            $table->index('trip_id');
            $table->index('policy_id');
            
            $table->unique(['trip_id', 'policy_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_policies');
    }
};
