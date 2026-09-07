<?php

namespace Database\Seeders;

use App\Models\DirectTransferPayment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DirectTransferPaymentSeeder extends Seeder
{
    public function run(): void
    {
        $paymentMethodId = 2;

        $payments = [
            [
                'payment_method_id' => $paymentMethodId,
                'uuid' => (string) Str::uuid(),
                'name' => 'حساب التحويل المباشر',
                'phone_number' => '777777777',
                'bank_name' => 'بنك الكريمي',
                'bank_account_number' => '1234567890',
            ],

            [
                'payment_method_id' => $paymentMethodId,
                'uuid' => (string) Str::uuid(),
                'name' => 'حساب التحويل المباشر الاحتياطي',
                'phone_number' => '733333333',
                'bank_name' => 'بنك اليمن والكويت',
                'bank_account_number' => '9876543210',
            ],
        ];

        foreach ($payments as $payment) {
            DirectTransferPayment::updateOrCreate(
                [
                    'payment_method_id' => $payment['payment_method_id'],
                    'bank_account_number' => $payment['bank_account_number'],
                ],
                $payment
            );
        }
    }
}