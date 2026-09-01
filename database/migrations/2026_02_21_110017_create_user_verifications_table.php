<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('code', 255);
            $table->enum('channel', ['email', 'sms', 'whatsapp']);
            $table->enum('purpose', ['verify_account', 'reset_password', 'change_phone', 'change_email']);
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->tinyInteger('attempts')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_verifications');
    }
};
