<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('policy_cancellation_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('policy_id')->constrained('policies')->onDelete('cascade');
            $table->integer('from_hours');
            $table->integer('to_hours');
            $table->decimal('fee_percentage', 5, 2);
            $table->timestamps();

            $table->index('policy_id');
            $table->index(['from_hours', 'to_hours']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('policy_cancellation_rules');
    }
};
