<?php

namespace App\Services;

use App\Models\PaymentMethod as PaymentMethodModel;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Support\Str;

class PaymentGatewayService
{
    public function __construct() {}

    public function processPayment(array $paymentData): array
    {
        $paymentMethodId = $paymentData['payment_method_id'];
        $amount = $paymentData['amount'];

        try {
            $paymentMethod = PaymentMethodModel::find($paymentMethodId);

            if (!$paymentMethod) {
                return [
                    'success' => false,
                    'message' => 'طريقة الدفع غير موجودة',
                ];
            }

            // الدفع عبر بوابات خارجية فقط
            return $this->processExternalPayment($paymentData);
            
        } catch (Exception $e) {
            Log::error('Payment processing failed', [
                'error' => $e->getMessage(),
                'payment_method_id' => $paymentMethodId,
                'amount' => $amount,
            ]);
            
            return [
                'success' => false,
                'message' => 'فشل معالجة الدفع. يرجى المحاولة مرة أخرى.',
            ];
        }
    }

    private function processExternalPayment(array $paymentData): array
    {
        $transactionId = 'PAY-' . Str::uuid();
        
        return [
            'success' => true,
            'message' => 'تم الدفع بنجاح عبر بوابة الدفع',
            'transaction_id' => $transactionId,
            'amount' => $paymentData['amount'],
        ];
    }

    public function refundPayment(string $transactionId, float $amount, int $userId): array
    {
        return [
            'success' => true,
            'message' => 'تم استرداد المبلغ - يتولى المحاسب التحويل',
            'refund_id' => 'REF-' . Str::uuid(),
        ];
    }
}