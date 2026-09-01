<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('type_user_code')->constrained('type_of_users')->onDelete('restrict');
            $table->foreignId('user_parent')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name')->nullable();
            $table->string('phone_number', 20)->nullable()->unique();
            $table->timestamp('phone_verified_at')->nullable();
            $table->string('email', 255)->unique()->nullable();
            $table->string('password', 255)->nullable();
            $table->foreignId('status_id')->constrained('user_statuses')->onDelete('restrict');
            $table->boolean('is_approved')->default(false);
            $table->softDeletes();
            $table->timestamps();
            
            // Performance indexes
            $table->index('email', 'idx_users_email');
            $table->index(['status_id', 'created_at'], 'idx_users_status_created');
            $table->index('name', 'idx_users_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
