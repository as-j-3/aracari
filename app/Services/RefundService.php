<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\User;
use App\Models\Transaction;
use App\Models\TransactionReference;
use App\Models\PolicyCancellationRule;
use App\Models\Trip;
use App\Constants\AccountCodes;
use App\Constants\BookingStatus as BookingStatusConst;
use App\Constants\TicketStatus;
use App\Services\AuditService;
use App\Constants\TransactionReferenceStatus;
use App\Services\DataIntegrityService;
use App\Services\NotificationService;
use App\Repositories\Contracts\TransactionRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;
use App\Services\Wallet\WalletService;
use App\Constants\PaymentMethod;


class RefundService
{
    private AuditService $auditService;
    private DataIntegrityService $dataIntegrityService;
    private NotificationService $notificationService;
    private TransactionRepositoryInterface $transactionRepository;
    private WalletService $walletService;

      public function __construct(
        AuditService $auditService,
        DataIntegrityService $dataIntegrityService,
        NotificationService $notificationService,
        TransactionRepositoryInterface $transactionRepository,
        WalletService $walletService
    ) {
        $this->auditService = $auditService;
        $this->dataIntegrityService = $dataIntegrityService;
        $this->notificationService = $notificationService;
        $this->transactionRepository = $transactionRepository;
        $this->walletService = $walletService;
    }

    /**
     * Cancel booking with refund processing and penalty calculation
     * 
     * This method handles booking cancellation with comprehensive validation,
     * penalty calculation based on timing, and full accounting reversals.
     * 
     * Business logic:
     * - Validates trip cancellation policies
     * - Calculates cancellation penalty based on time remaining
     * - Updates booking status to cancelled
     * - Cancels associated tickets and reservations
     * - Releases seat capacity
     * - Processes accounting reversals for revenue, taxes, and commissions
     * 
     * Security considerations:
     * - Database transactions for atomicity
     * - Data integrity validation
     * - Comprehensive error logging
     * - Status transition validation
     * 
     * @param Booking $booking Booking to cancel
     * @param User $user User performing the cancellation
     * @return array Cancellation result with success status, refund amount, and penalty amount
     */
    public function cancelBooking(Booking $booking, User $user, array $context = []): array
    {
        if ($booking->booking_status_id === BookingStatusConst::CANCELLED ||
            $booking->booking_status_id === BookingStatusConst::CANCELLED_BY_USER ||
            $booking->booking_status_id === BookingStatusConst::CANCELLED_BY_ADMIN) {
            return ['success' => false, 'message' => 'الحجز ملغى مسبقاً'];
        }

        $boardedTicketsExist = \App\Models\Ticket::whereHas('tripReservation', function ($q) use ($booking) {
            $q->where('booking_id', $booking->id);
        })->where('status', TicketStatus::BOARDED)->exists();

        if ($boardedTicketsExist) {
            return ['success' => false, 'message' => 'لا يمكن إلغاء الحجز، تم تأكيد صعود بعض المسافرين للباص'];
        }

        $policyCheck = $this->checkTripCancellationPolicy($booking);
        if (!$policyCheck['allowed']) {
            return ['success' => false, 'message' => $policyCheck['reason']];
        }

        try {
            DB::beginTransaction();
            
            $oldData = $booking->getOriginal(); 
            $isOwner = $booking->user_id === $user->id;
            $cancellationStatus = $this->resolveCancellationStatus($isOwner);

            $integrityErrors = $this->dataIntegrityService->validateStatusTransition(
                $booking->booking_status_id,
                $cancellationStatus
            );

            if (!empty($integrityErrors)) {
                DB::rollBack();
                return ['success' => false, 'message' => __('messages.invalid_status_transition')];
            }

            $policyPenaltyAmount = isset($policyCheck['refund_percentage'])
                ? ($booking->amount * (100 - (float) $policyCheck['refund_percentage'])) / 100
                : 0;
            $penaltyAmount = min($booking->amount, max($this->calculateCancellationPenalty($booking), $policyPenaltyAmount));
            $refundAmount = max(0, $booking->amount - $penaltyAmount);
            
            Log::info('Refund values', [
                'refund_amount' => $refundAmount,
                'penalty_amount' => $penaltyAmount,
                'booking_amount' => $booking->amount,
            ]);
            
            if ($refundAmount > 0) {
                $amountErrors = $this->dataIntegrityService->validateFinancialData($refundAmount);
                if (!empty($amountErrors)) {
                    DB::rollBack();
                    return ['success' => false, 'message' => __('messages.invalid_financial_data')];
                }
            }

            $totalBookingTickets = $booking->tripReservations->sum(fn ($reservation) => $reservation->tickets()->count());
            $feePerTicket = $totalBookingTickets > 0 ? $penaltyAmount / $totalBookingTickets : 0;
            $refundPerTicket = $totalBookingTickets > 0 ? $refundAmount / $totalBookingTickets : 0;

            $tripIds = $booking->tripReservations->pluck('trip_id')->toArray();
            $lockedTrips = Trip::whereIn('id', $tripIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($booking->tripReservations as $reservation) {
                $lockedTrip = $lockedTrips[$reservation->trip_id] ?? null;

                if (!$lockedTrip) {
                    throw new \Exception("فشل قفل الرحلة {$reservation->trip_id} لاسترجاع المقاعد");
                }

                if ($lockedTrip->departure_time <= now()) {
                    throw new \Exception("لا يمكن استرجاع مقاعد رحلة قد بدأت");
                }
                
                $reservation->update(['status' => 'cancelled']);
                
                $reservation->tickets()->update([
                    'status' => TicketStatus::CANCELLED,
                    'cancellation_fee' => $feePerTicket,
                    'refund_amount' => $refundPerTicket,
                    'cancelled_at' => now(),
                ]);
            }

            $booking->update([
                'booking_status_id' => $cancellationStatus,
                'cancelled_at' => now(),
            ]);
            
            $refundAmount = max(0, $booking->amount - $penaltyAmount);
            if ($booking->payment && $booking->payment->payment_method_id === PaymentMethod::WALLET && $refundAmount > 0) {
                $walletAccount = $this->walletService->getOrCreateAccount($booking->user_id, $booking->currency_id);
                $this->walletService->credit($walletAccount, $refundAmount, 'استرداد حجز ملغي', 'BookingRefund', $booking->id);
            }
            $booking->loadMissing('tripReservations.trip.vehicle');
            $this->processRefundAccounting($booking, $refundAmount, $penaltyAmount);

            //  Audit + Notification في afterCommit
            DB::afterCommit(function () use ($user, $booking, $oldData, $cancellationStatus, $refundAmount, $penaltyAmount, $context) {
                $this->auditService->log(
                    $user->id,
                    'cancelled',
                    'bookings',
                    $booking->id,
                    $oldData,
                    array_merge([
                        'booking_status_id' => $cancellationStatus,
                        'refund_amount' => $refundAmount,
                        'penalty_amount' => $penaltyAmount,
                        'commission_refund' => $booking->commission_amount ?? 0,
                    ], $context)
                );
                $this->notificationService->notifyBookingSafe(
                    $booking,
                    $refundAmount,
                    \App\Constants\NotificationTypes::BOOKING_CANCELLED,
                    $penaltyAmount
                );
            });

            DB::commit();

            return [
                'success' => true,
                'message' => 'تم إلغاء الحجز بنجاح وإرجاع المبالغ المستحقة.',
                'refund_amount' => $refundAmount,
                'penalty_amount' => $penaltyAmount
            ];
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Refund failed', [
                'error' => $e->getMessage(),
                'booking_id' => $booking->id,
                'user_id' => $user->id,
                'trace' => $e->getTraceAsString(),
            ]);
            
            if ($e instanceof \Illuminate\Database\QueryException) {
                return ['success' => false, 'message' => __('messages.database_error')];
            } else {
                return ['success' => false, 'message' => __('messages.refund_failed')];
            }
        }
    }

    /**
     * Validate trip cancellation policies
     * 
     * This method checks if the booking can be cancelled based on
     * trip-specific policies and refund rules.
     * 
     * Business logic:
     * - Checks for no-refund policies
     * - Applies partial refund percentages (50%, 25%)
     * - Returns full refund (100%) if no special policy applies
     * 
     * @param Booking $booking Booking to validate
     * @return array Validation result with allowed status and refund percentage
     */
    private function checkTripCancellationPolicy(Booking $booking): array
    {
        $trip = $booking->tripReservations()->first()?->trip;
        if (!$trip) {
            return ['allowed' => true, 'reason' => 'لا توجد سياسات محددة'];
        }

        $tripPolicies = $trip->policies; // Many-to-Many relationship
        
        foreach ($tripPolicies as $policy) {
            if ($policy->name === 'no_refund') {
                return ['allowed' => false, 'reason' => 'سياسة الرحلة تمنع الإلغاء'];
            }
            if ($policy->name === 'partial_refund_50') {
                return ['allowed' => true, 'refund_percentage' => 50];
            }
            if ($policy->name === 'partial_refund_25') {
                return ['allowed' => true, 'refund_percentage' => 25];
            }
        }
        
        return ['allowed' => true, 'refund_percentage' => 100];
    }

  private function resolveCancellationStatus(bool $isOwner): int
    {
    //  استخدم الثوابت مباشرة - مع تحديد نوع الإلغاء حسب الدور
    return $isOwner
        ? BookingStatusConst::CANCELLED_BY_USER    // 5
        : BookingStatusConst::CANCELLED;
        //return BookingStatusConst::CANCELLED;          // 3
    }          

    /**
     * Calculate cancellation penalty based on time remaining
     * 
     * This method determines the cancellation penalty amount based on
     * the time remaining until trip departure and configured rules.
     * 
     * Business logic:
     * - 100% penalty if trip has already departed
     * - Applies percentage-based penalties based on time ranges
     * - Returns 0 if no penalty rule applies (full refund)
     * 
     * @param Booking $booking Booking to calculate penalty for
     * @return float Calculated penalty amount
     */
    private function calculateCancellationPenalty(Booking $booking): float
    {
        $trip = $booking->tripReservations()->first()?->trip;
        if (!$trip) return 0;

        $hoursRemaining = now()->diffInHours($trip->departure_time, false);
        
        if ($hoursRemaining <= 0) {
            // انطلقت الرحلة، غرامة 100%
            return $booking->amount;
        }

        // البحث عن القاعدة المناسبة بناءً على النطاق الزمني
        $rule = PolicyCancellationRule::where('from_hours', '<=', $hoursRemaining)
                    ->where('to_hours', '>=', $hoursRemaining)
                    ->first();

        if ($rule) {
            return ($booking->amount * $rule->fee_percentage) / 100;
        }

        return 0; // استرجاع كامل إذا لم يتم العثور على قاعدة
    }
    /**
     * Process refund accounting reversals
     * 
     * This method creates accounting entries to reverse the original
     * booking transactions and record penalties as revenue.
     * 
     * Accounting logic:
     * - Credits customer account with refund amount
     * - Debits trip revenue for full booking amount
     * - Credits cancellation penalty account with penalty amount
     * - Reverses taxes if applicable
     * - Reverses commissions if applicable
     * 
     * @param Booking $booking Booking being cancelled
     * @param float $refundAmount Amount to refund to customer
     * @param float $penaltyAmount Penalty amount to record as revenue
     * @return void
     */
    public function processRefundAccounting(Booking $booking, float $refundAmount, float $penaltyAmount): void
    {
        $totalPaid = round((float) $booking->amount, 2);
        $rate = $booking->exchange_rate ?: 1;
        
        $booking->loadMissing(['taxes', 'tripReservations.trip.vehicle']);
        $taxAmount = round((float) $booking->taxes->sum('tax_amount'), 2);
        $commissionAmount = round((float) $booking->commission_amount, 2);
        $providerAmount = round($totalPaid - $commissionAmount - $taxAmount, 2);
        
        $transactionRef = $this->transactionRepository->createReference([
            'reference_number' => 'REF-' . strtoupper(Str::random(10)),
            'reference_type' => 'refund',
            'reference_id' => $booking->id,
            'created_by' => auth()->id() ?? $booking->user_id,
            'status' => TransactionReferenceStatus::DRAFT,
        ]);

        $providerId = $booking->tripReservations->first()?->trip?->vehicle?->user_id;
        $providerCode = $providerId ? AccountCodes::providerAccount($providerId) : AccountCodes::LIABILITIES;

        $bookingShortId = '#' . $booking->id;
        $customerName = $booking->user?->name ?? 'عميل';

        $commonData = [
            'transaction_reference_id' => $transactionRef->id,
            'currency_id' => $booking->currency_id,
            'exchange_rate' => $booking->exchange_rate,
        ];

        // 1. استرجاع للعميل (دائن)
        $this->transactionRepository->createTransaction(array_merge($commonData, [
            'account_code' => AccountCodes::ASSETS,
            'transaction_type' => 'credit',
            'amount' => $refundAmount,
            'base_amount' => round($refundAmount / $rate, 2),
            'description' => "استرجاع مبلغ للعميل {$customerName} - إلغاء الحجز {$bookingShortId}",
        ]));

        // 2. عكس مستحقات المزود (مدين)
        $this->transactionRepository->createTransaction(array_merge($commonData, [
            'account_code' => $providerCode,
            'transaction_type' => 'debit',
            'amount' => $providerAmount,
            'base_amount' => round($providerAmount / $rate, 2),
            'description' => "عكس مستحقات المزود - إلغاء الحجز {$bookingShortId} (العميل: {$customerName})",
        ]));

        // 3. عكس الضريبة (مدين)
        if ($taxAmount > 0) {
            $this->transactionRepository->createTransaction(array_merge($commonData, [
                'account_code' => AccountCodes::LIABILITIES,
                'transaction_type' => 'debit',
                'amount' => $taxAmount,
                'base_amount' => round($taxAmount / $rate, 2),
                'description' => "عكس ضريبة - إلغاء الحجز {$bookingShortId}",
            ]));
        }

        // 4. عكس عمولة المنصة (مدين)
        if ($commissionAmount > 0) {
            $this->transactionRepository->createTransaction(array_merge($commonData, [
                'account_code' => AccountCodes::REVENUE,
                'transaction_type' => 'debit',
                'amount' => $commissionAmount,
                'base_amount' => round($commissionAmount / $rate, 2),
                'description' => "عكس عمولة المنصة - إلغاء الحجز {$bookingShortId}",
            ]));
        }

        // 5. تسجيل الغرامة كإيراد جديد (دائن)
        if ($penaltyAmount > 0) {
            $this->transactionRepository->createTransaction(array_merge($commonData, [
                'account_code' => AccountCodes::REVENUE,
                'transaction_type' => 'credit',
                'amount' => $penaltyAmount,
                'base_amount' => round($penaltyAmount / $rate, 2),
                'description' => "إيراد غرامة إلغاء - الحجز {$bookingShortId} (العميل: {$customerName})",
            ]));
        }

        if (!$this->transactionRepository->validateEntryBalance($transactionRef->id)) {
            throw new \RuntimeException("خطأ محاسبي: قيد الإلغاء غير متوازن للمرجع {$transactionRef->id}");
        }
    }
    
    private function validateBalancedReference(int $referenceId): void
    {
        $debits = Transaction::where('transaction_reference_id', $referenceId)
            ->where('transaction_type', 'debit')
            ->sum('amount');
        $credits = Transaction::where('transaction_reference_id', $referenceId)
            ->where('transaction_type', 'credit')
            ->sum('amount');

        if (abs($debits - $credits) > 0.01) {
            throw new \RuntimeException("Unbalanced refund entry: debits={$debits}, credits={$credits}");
        }
    }
}
