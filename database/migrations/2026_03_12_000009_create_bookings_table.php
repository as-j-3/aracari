<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained('provider_branches')->nullOnDelete();
            $table->foreignId('booking_status_id')->constrained('booking_statuses')->onDelete('restrict');
            $table->decimal('amount', 10, 2);
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->onDelete('set null');
            $table->decimal('exchange_rate', 10, 4)->nullable();
            $table->decimal('base_amount', 10, 2)->nullable();
            $table->foreignId('commission_id')->nullable()->constrained('commissions')->nullOnDelete();
            $table->decimal('commission_value', 10, 2)->nullable();
            $table->decimal('commission_amount', 10, 2)->nullable();
            $table->enum('commission_type_fixed_or_percentage', ['fixed', 'percentage'])->default('percentage'); // إضافة نوع العمولة
            $table->text('note')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
            
            $table->index(['user_id', 'booking_status_id'], 'idx_bookings_user_status');
            $table->index(['booking_status_id'], 'idx_bookings_status');
            $table->index(['created_at'], 'idx_bookings_created');
            $table->index(['created_at', 'booking_status_id']);
            $table->index('amount');
        });

        Schema::table('booking_payments', function (Blueprint $table) {
            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('booking_payments', function (Blueprint $table) {
            $table->dropForeign(['booking_id']);
        });
        Schema::dropIfExists('bookings');
    }
};
