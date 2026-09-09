<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $cols = \Schema::getColumnListing('accounts');

            // حذف index القديم إن وجد
            $indexes = collect(\DB::select("
                SELECT INDEX_NAME
                FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'accounts'
                  AND INDEX_NAME = 'accounts_account_code_level_index'
            "));
            if ($indexes->isNotEmpty()) {
                $table->dropIndex(['account_code', 'level']);
            }

            // حذف level إن وجد
            if (in_array('level', $cols)) {
                $table->dropColumn('level');
            }

            // إضافة normal_account إن لم يوجد
            if (!in_array('normal_account', $cols)) {
                $table->enum('normal_account', ['main', 'sub'])
                    ->default('main')
                    ->after('account_type');
            }

            // إضافة account_nature إن لم يوجد
            if (!in_array('account_nature', $cols)) {
                $table->enum('account_nature', ['debit', 'credit'])
                    ->after('normal_account');
            }

            // إضافة final_account_id إن لم يوجد
            if (!in_array('final_account_id', $cols)) {
                $table->unsignedBigInteger('final_account_id')
                    ->nullable()
                    ->after('account_nature');

                $table->foreign('final_account_id')
                    ->references('final_account_id')
                    ->on('final_accounts')
                    ->nullOnDelete();
            }

            // index على account_code
            $codeIdx = collect(\DB::select("
                SELECT INDEX_NAME
                FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'accounts'
                  AND INDEX_NAME = 'accounts_account_code_index'
            "));
            if ($codeIdx->isEmpty()) {
                $table->index('account_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            // إزالة الـ FK فقط لو موجود
            $fks = collect(\DB::select("
                SELECT CONSTRAINT_NAME
                FROM information_schema.TABLE_CONSTRAINTS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'accounts'
                  AND CONSTRAINT_TYPE = 'FOREIGN KEY'
                  AND CONSTRAINT_NAME = 'accounts_final_account_id_foreign'
            "));

            if ($fks->isNotEmpty()) {
                $table->dropForeign(['final_account_id']);
            }

            $cols = \Schema::getColumnListing('accounts');

            $toDrop = array_filter(
                ['final_account_id', 'account_nature', 'normal_account'],
                fn($c) => in_array($c, $cols)
            );

            if (!empty($toDrop)) {
                $table->dropColumn(array_values($toDrop));
            }

            if (!in_array('level', $cols)) {
                $table->tinyInteger('level')
                    ->default(1)
                    ->after('account_type');
            }
        });
    }
};