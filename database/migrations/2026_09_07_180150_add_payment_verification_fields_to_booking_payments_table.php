<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_payments', function (Blueprint $table) {

            // حالة الدفع
            $table->string('paid_status')
                ->default('pending')
                ->after('base_amount');

            // المستخدم الذي قام بتأكيد/رفض الدفع
            $table->foreignId('verified_by_user')
                ->nullable()
                ->after('paid_status')
                ->constrained('users')
                ->nullOnDelete();

            // تاريخ التحقق
            $table->timestamp('verified_at')
                ->nullable()
                ->after('verified_by_user');

            // سبب رفض الدفع
            $table->text('reject_reason')
                ->nullable()
                ->after('verified_at');

            // Indexes للتقارير والبحث
            $table->index('paid_status');
            $table->index('verified_by_user');
            $table->index('verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('booking_payments', function (Blueprint $table) {

            $table->dropForeign([
                'verified_by_user',
            ]);

            $table->dropIndex([
                'booking_payments_paid_status_index',
            ]);

            $table->dropIndex([
                'booking_payments_verified_by_user_index',
            ]);

            $table->dropIndex([
                'booking_payments_verified_at_index',
            ]);

            $table->dropColumn([
                'paid_status',
                'verified_by_user',
                'verified_at',
                'reject_reason',
            ]);
        });
    }
};