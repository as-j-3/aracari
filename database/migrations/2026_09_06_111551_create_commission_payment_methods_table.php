<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commission_payment_methods', function (Blueprint $table) {
            $table->id();

            // المفتاح الأجنبي لوسيلة الدفع (مع التأكد من اسم العمود المعتمد لديك في payment_methods)
            $table->foreignId('payment_method_id')->constrained('payment_methods')->cascadeOnDelete();

            // حقول تفاصيل العمولة
            $table->string('com_type_rate_or_amount')->comment('percentage or fixed');
            $table->decimal('com_amount', 10, 2)->nullable();
            $table->decimal('com_rate', 5, 2)->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_payment_methods');
    }
};