<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'country_taxes',
            'provider_taxes',
            'commission_taxes',
            'commission_payment_methods',
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                // 1. إضافة العمود بدون قيد الفرادة مؤقتاً لتفادي أخطاء التكرار أثناء الإنشاء
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    if (!Schema::hasColumn($tableName, 'uuid')) {
                        $table->uuid('uuid')->nullable()->after('id');
                    }
                });

                // 2. تعبئة السجلات القديمة بقيم UUID فريدة لكل سجل
                $records = DB::table($tableName)->whereNull('uuid')->get();
                foreach ($records as $record) {
                    DB::table($tableName)
                        ->where('id', $record->id)
                        ->update(['uuid' => (string) Str::uuid()]);
                }

                // 3. تطبيق القيد unique و make it not null
                Schema::table($tableName, function (Blueprint $table) {
                    $table->uuid('uuid')->nullable(false)->unique()->change();
                });
            }
        }
    }

    public function down(): void
    {
        $tables = [
            'country_taxes',
            'provider_taxes',
            'commission_taxes',
            'commission_payment_methods',
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'uuid')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropColumn('uuid');
                });
            }
        }
    }
};