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
        Schema::table('users', function (Blueprint $table) {
            // نستخدم nullable في حال كان هناك مستخدمون مسجلون سابقاً بدون كود حساب
            $table->string('account_code', 20)->nullable()->unique()->after('uuid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['account_code']);
            $table->dropColumn('account_code');
        });
    }
};