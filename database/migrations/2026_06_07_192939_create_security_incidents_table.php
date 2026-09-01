<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_incidents', function (Blueprint $table) {
            $table->id();
            $table->string('incident_type', 50);        
            $table->string('severity', 20)->default('medium');
            $table->string('ip_address', 45)->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('user_agent')->nullable();
            $table->string('request_method', 10)->nullable();
            $table->string('request_path', 500)->nullable();
            $table->json('request_inputs')->nullable();
            $table->unsignedInteger('attempt_count')->default(1);
            $table->boolean('was_blocked')->default(false);
            $table->timestamp('occurred_at')->useCurrent();
            $table->timestamps();
            
            $table->index(['incident_type', 'severity', 'occurred_at']);
            $table->index('ip_address');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_incidents');
    }
};