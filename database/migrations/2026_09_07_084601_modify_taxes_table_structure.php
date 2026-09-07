<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('taxes', function (Blueprint $table) {
            // إسقاط المفتاح الأجنبي لـ currency_id أولاً
            $table->dropForeign(['currency_id']);

            // حذف الأعمدة التي تم نقلها إلى جدول country_taxes
            $table->dropColumn([
                'type',
                'rate',
                'amount',
                'currency_id'
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('taxes', function (Blueprint $table) {
            // إرجاع الأعمدة في حال التراجع (Rollback)
            $table->enum('type', ['percentage', 'fixed'])->default('fixed')->after('name');
            $table->decimal('rate', 5, 2)->nullable()->after('type');
            $table->decimal('amount', 8, 2)->nullable()->after('rate');
            $table->foreignId('currency_id')->nullable()->after('amount')->constrained('currencies')->onDelete('set null');
        });
    }
};