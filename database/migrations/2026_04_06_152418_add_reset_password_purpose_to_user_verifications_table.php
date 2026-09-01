<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('user_verifications', function (Blueprint $table) {
            $table->enum('purpose', ['verify_account', 'reset_password', 'change_phone', 'change_email', 'password_reset','verify_phone'])->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_verifications', function (Blueprint $table) {
            $table->enum('purpose', ['verify_account', 'reset_password', 'change_phone', 'change_email'])->change();
        });
    }
};
