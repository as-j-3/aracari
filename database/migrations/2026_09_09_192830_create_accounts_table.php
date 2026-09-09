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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('account_code', 20)->unique();
            $table->string('account_name');

            // نوع الحساب: 1 = رئيسي (Main)، 0 = فرعي (Sub)
            $table->boolean('type_account')->default(0)->comment('1: Sub, 0: Main');

            // طبيعة الحساب: مدين (debit) أو دائن (credit)
            $table->enum('account_nature', ['debit', 'credit']);

            // المفاتيح الأجنبية
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->unsignedBigInteger('final_account_id')->nullable();
            $table->unsignedBigInteger('created_by_user')->nullable();

            $table->timestamps();

            // العلاقات (Foreign Keys)
            $table->foreign('parent_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('final_account_id')
                ->references('final_account_id') // 👈 التأكد من اسم العمود في جدول final_accounts
                ->on('final_accounts')
                ->onDelete('set null');
            $table->foreign('created_by_user')->references('id')->on('users')->onDelete('set null');

            // الفهارس
            $table->index(['account_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
