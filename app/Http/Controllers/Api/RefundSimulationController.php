<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Trip;
use App\Models\BookingTax;
use App\Models\BookingCommission;
use App\Models\Transaction;
use App\Models\TransactionReference;
use App\Constants\AccountCodes;
use Illuminate\Http\JsonResponse;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class RefundSimulationController extends Controller
{
    use ApiResponse;


    public function simulateRefund(Request $request): JsonResponse
    {
        if (!auth()->user()?->isAdmin()) {
            return $this->error('غير مصرح لك بمحاكاة استرداد الحجز', 403);
        }
        $data = $request->validate([
            'booking_uuid' => 'required|string|exists:bookings,uuid',
        ]);

        try {
            $booking = Booking::where('uuid', $data['booking_uuid'])
                ->with(['tripReservations.trip', 'bookingTaxes', 'bookingCommissions'])
                ->first();

            if (!$booking) {
                return $this->error('الحجز غير موجود');
            }

            $isAdmin = $request->user()->isAdmin();
            if (!$isAdmin && $booking->user_id !== $request->user()->id) {
                return $this->error('غير مصرح لك بمحاكاة استرداد هذا الحجز', 403);
            }

            $totalPaid = $booking->amount;
            $totalTax = $booking->bookingTaxes->sum('tax_amount');
            $totalCommission = $booking->bookingCommissions->sum('commission_amount') ?? 0;
            $totalAmount = $totalPaid + $totalTax + $totalCommission;

            // جلب جميع القيود المرتبطة بالحجز
            $transactions = Transaction::whereHas('transactionReference', function ($query) use ($booking) {
                $query->where('description', 'like', '%حجز رقم: ' . $booking->uuid . '%');
            })->get();

            // حساب مجموع القيود العكسية
            $totalRefundToCustomer = $transactions
                ->where('transaction_type', 'credit')
                ->where('account_code', 'like', '1210_%') // حساب العميل
                ->sum('amount');

            $totalDebitFromRevenue = $transactions
                ->where('transaction_type', 'debit')
                ->where('account_code', AccountCodes::TRIP_REVENUE)
                ->sum('amount');

            $totalCreditPenalties = $transactions
                ->where('transaction_type', 'credit')
                ->where('account_code', AccountCodes::CANCELLATION_PENALTY)
                ->sum('amount');

            $totalTaxRefund = $transactions
                ->where('transaction_type', 'credit')
                ->where('account_code', AccountCodes::TAX_PAYABLE)
                ->sum('amount');

            $totalCommissionRefund = $transactions
                ->where('transaction_type', 'credit')
                ->where('account_code', AccountCodes::COMMISSION_PAYABLE)
                ->sum('amount');

            return $this->success([
                'booking_info' => [
                    'uuid' => $booking->uuid,
                    'total_paid' => $totalPaid,
                    'total_tax' => $totalTax,
                    'total_commission' => $totalCommission,
                    'total_amount' => $totalAmount,
                ],
                'refund_transactions' => [
                    'total_refund_to_customer' => $totalRefundToCustomer,
                    'total_debit_from_revenue' => $totalDebitFromRevenue,
                    'total_credit_penalties' => $totalCreditPenalties,
                    'total_tax_refund' => $totalTaxRefund,
                    'total_commission_refund' => $totalCommissionRefund,
                ],
                'verification' => [
                    'should_equal' => $totalAmount,
                    'actual_total_refunded' => $totalRefundToCustomer + $totalTaxRefund + $totalCommissionRefund,
                    'is_balanced' => abs($totalAmount - ($totalRefundToCustomer + $totalTaxRefund + $totalCommissionRefund)) < 0.01,
                    'difference' => $totalAmount - ($totalRefundToCustomer + $totalTaxRefund + $totalCommissionRefund),
                ],
                'transactions_count' => $transactions->count(),
                'transactions' => $transactions->map(function ($transaction) {
                    return [
                        'account_code' => $transaction->account_code,
                        'transaction_type' => $transaction->transaction_type,
                        'amount' => $transaction->amount,
                        'description' => $transaction->description,
                    ];
                }),
            ], 'اكتمل الاختبار التجريبي بنجاح');
        } catch (\Exception $e) {
            return $this->error('فشل الاختبار: ' . $e->getMessage());
        }
    }
}
