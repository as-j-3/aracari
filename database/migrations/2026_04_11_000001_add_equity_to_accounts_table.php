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
        $dbType = DB::getDriverName();

        if ($dbType === 'mysql') {
            DB::statement("ALTER TABLE accounts MODIFY COLUMN account_type ENUM('asset', 'liability', 'revenue', 'expense', 'equity')");
        } else {
            Schema::table('accounts', function (Blueprint $table) {
                $table->dropColumn('account_type');
            });

            Schema::table('accounts', function (Blueprint $table) {
                $table->enum('account_type', ['asset', 'liability', 'revenue', 'expense', 'equity'])->default('asset');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Check database type and use appropriate method
        $dbType = DB::getDriverName();

        if ($dbType === 'mysql') {
            // MySQL: Use MODIFY COLUMN
            DB::statement("ALTER TABLE accounts MODIFY COLUMN account_type ENUM('asset', 'liability', 'revenue', 'expense')");
        } else {
            // SQLite: Drop and recreate column
            Schema::table('accounts', function (Blueprint $table) {
                $table->dropColumn('account_type');
            });

            Schema::table('accounts', function (Blueprint $table) {
                $table->enum('account_type', ['asset', 'liability', 'revenue', 'expense'])->default('asset');
            });
        }
    }
};
