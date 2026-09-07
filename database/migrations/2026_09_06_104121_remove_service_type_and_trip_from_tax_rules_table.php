<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tax_rules', function (Blueprint $table) {
            // 1. إسقاط قيود المفاتيح الأجنبية أولاً
            $table->dropForeign(['service_type_id']);
            $table->dropForeign(['trip_id']);

            // 2. حذف الأعمدة من الجدول
            $table->dropColumn(['service_type_id', 'trip_id']);
        });
    }

    public function down(): void
    {
        Schema::table('tax_rules', function (Blueprint $table) {
            $table->foreignId('service_type_id')->nullable()->constrained('type_of_services')->onDelete('set null');
            $table->foreignId('trip_id')->nullable()->constrained('trips')->onDelete('cascade');
        });
    }
};