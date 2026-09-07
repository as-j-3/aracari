<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('taxes', function (Blueprint $table) {
            // إضافة الحقل مع تحديد قيمة افتراضية كـ boolean وموقعه بعد currency_id
            $table->boolean('is_visible_for_customer')
                  ->default(true)
                  ->after('currency_id');
        });
    }

    public function down(): void
    {
        Schema::table('taxes', function (Blueprint $table) {
            $table->dropColumn('is_visible_for_customer');
        });
    }
};