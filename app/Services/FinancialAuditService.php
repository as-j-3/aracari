<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Transaction;
use App\Models\TransactionReference;
use App\Models\BookingPayment;
use App\Models\BookingTax;
use App\Models\CouponUsage;
use Illuminate\Support\Facades\Log;

class FinancialAuditService
{
    /**
     * تسجيل جميع العمليات المالية في الحجز
     */
    public function logBookingFinancials(Booking $booking, array $calculation, int $userId): void
    {
        $auditData = [
            'booking_uuid' => $booking->uuid,
            'user_id' => $booking->user_id,
            'amount' => $booking->amount,
            'base_amount' => $calculation['base_amount'] ?? 0,
            'discount_amount' => $calculation['discount_amount'] ?? 0,
            'tax_total' => $calculation['tax_total'] ?? 0,
            'commission_amount' => $booking->commission_amount ?? 0,
            'payment_method_id' => $booking->payment?->payment_method_id,
            'created_by' => $userId,
            'timestamp' => now()->toISOString(),
        ];

        Log::channel('financial')->info('Booking created', $auditData);
        
        // تسجيل كل ضريبة بشكل منفصل
        foreach ($booking->taxes as $tax) {
            Log::channel('financial')->info('Tax applied', [
                'booking_uuid' => $booking->uuid,
                'tax_id' => $tax->tax_id,
                'tax_name' => $tax->tax_name,
                'tax_amount' => $tax->tax_amount,
                'tax_rate' => $tax->tax_rate,
                'tax_type' => $tax->tax_type_rate_or_amount,
            ]);
        }

        // تسجيل العمولة
        if ($booking->commission_amount > 0) {
            Log::channel('financial')->info('Commission applied', [
                'booking_uuid' => $booking->uuid,
                'commission_id' => $booking->commission_id,
                'commission_value' => $booking->commission_value,
                'commission_amount' => $booking->commission_amount,
                'commission_type' => $booking->commission_type_fixed_or_percentage,
            ]);
        }

        // تسجيل استخدام الكوبون
        if ($booking->couponUsages()->exists()) {
            $couponUsage = $booking->couponUsages()->first();
            Log::channel('financial')->info('Coupon used', [
                'booking_uuid' => $booking->uuid,
                'coupon_id' => $couponUsage->coupon_id,
                'discount_amount' => $couponUsage->discount_amount,
                'user_id' => $couponUsage->user_id,
            ]);
        }
    }

    /**
     * تسجيل عملية الإلغاء المالية
     */
    public function logBookingCancellation(Booking $booking, float $refundAmount, float $cancellationFee, int $userId): void
    {
        Log::channel('financial')->warning('Booking cancelled', [
            'booking_uuid' => $booking->uuid,
            'user_id' => $booking->user_id,
            'original_amount' => $booking->amount,
            'refund_amount' => $refundAmount,
            'cancellation_fee' => $cancellationFee,
            'commission_refunded' => $booking->commission_amount ?? 0,
            'cancelled_by' => $userId,
            'cancelled_at' => $booking->cancelled_at,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * تسجيل المعاملات المالية
     */
    public function logTransaction(TransactionReference $transactionRef, array $transactions): void
    {
        foreach ($transactions as $transaction) {
            Log::channel('financial')->info('Transaction recorded', [
                'reference_number' => $transactionRef->reference_number,
                'transaction_id' => $transaction->id,
                'account_code' => $transaction->account_code,
                'transaction_type' => $transaction->transaction_type,
                'amount' => $transaction->amount,
                'description' => $transaction->description,
                'created_by' => $transactionRef->created_by,
                'timestamp' => now()->toISOString(),
            ]);
        }
    }

    /**
     * التحقق من سلامة البيانات المالية
     */
    public function validateFinancialIntegrity(Booking $booking): array
    {
        $issues = [];

        // التحقق من تطابق المبلغ الإجمالي
        $expectedTotal = $this->calculateExpectedTotal($booking);
        if (abs($booking->amount - $expectedTotal) > 0.01) {
            $issues[] = [
                'type' => 'amount_mismatch',
                'expected' => $expectedTotal,
                'actual' => $booking->amount,
                'difference' => abs($booking->amount - $expectedTotal),
            ];
        }

        // التحقق من وجود دفع
        if (!$booking->payment) {
            $issues[] = [
                'type' => 'missing_payment',
                'message' => 'No payment record found for booking',
            ];
        }

        // التحقق من تطابق مبلغ الدفع
        if ($booking->payment && $booking->payment->payed_amount !== $booking->amount) {
            $issues[] = [
                'type' => 'payment_amount_mismatch',
                'booking_amount' => $booking->amount,
                'payment_amount' => $booking->payment->payed_amount,
            ];
        }

        // التحقق من المعاملات المحاسبية
        $transactionCount = Transaction::whereHas('transactionReference', function ($query) use ($booking) {
            $query->where('description', 'like', "%{$booking->uuid}%");
        })->count();

        if ($transactionCount < 2) { // يجب أن يكون على الأقل debit و credit
            $issues[] = [
                'type' => 'insufficient_transactions',
                'expected_min' => 2,
                'actual' => $transactionCount,
            ];
        }

        return $issues;
    }

    /**
     * حساب المبلغ المتوقع للحجز
     */
    private function calculateExpectedTotal(Booking $booking): float
    {
        $baseAmount = $booking->tripReservations->sum(function ($reservation) {
            return $reservation->tickets->sum('price_at_booking');
        });

        $discountAmount = $booking->couponUsages()->sum('discount_amount');
        $taxAmount = $booking->taxes()->sum('tax_amount');

        return ($baseAmount - $discountAmount) + $taxAmount;
    }

    /**
     * إنشاء تقرير مالي شامل
     */
    public function generateFinancialReport(Booking $booking): array
    {
        return [
            'booking' => [
                'uuid' => $booking->uuid,
                'amount' => $booking->amount,
                'status' => $booking->status->status_name,
                'created_at' => $booking->created_at,
            ],
            'financial_breakdown' => [
                'base_amount' => $booking->tripReservations->sum(function ($r) {
                    return $r->tickets->sum('price_at_booking');
                }),
                'discount_amount' => $booking->couponUsages()->sum('discount_amount'),
                'tax_amount' => $booking->taxes()->sum('tax_amount'),
                'commission_amount' => $booking->commission_amount ?? 0,
                'final_amount' => $booking->amount,
            ],
            'transactions' => Transaction::whereHas('transactionReference', function ($query) use ($booking) {
                $query->where('description', 'like', "%{$booking->uuid}%");
            })->get()->map(function ($transaction) {
                return [
                    'account_code' => $transaction->account_code,
                    'type' => $transaction->transaction_type,
                    'amount' => $transaction->amount,
                    'description' => $transaction->description,
                    'created_at' => $transaction->created_at,
                ];
            }),
            'integrity_check' => $this->validateFinancialIntegrity($booking),
        ];
    }
}
