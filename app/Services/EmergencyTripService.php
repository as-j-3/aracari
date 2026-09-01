<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Transaction;
use App\Models\TransactionReference;
use App\Models\Trip;
use App\Models\TripReservation;
use App\Constants\AccountCodes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Service for Emergency Trip operations
 * 
 * Handles business logic for emergency trip cancellation and rescheduling
 * Follows SOLID principles with clean, maintainable code
 */
class EmergencyTripService
{
    protected AuditService $auditService;
    protected NotificationService $notificationService;

    public function __construct(
        AuditService $auditService,
        NotificationService $notificationService,
    ) {
        $this->auditService = $auditService;
        $this->notificationService = $notificationService;
    }

    /**
     * Emergency trip cancellation with notifications
     * 
     * Cancels a trip and processes all related bookings with refunds
     * 
     * Business logic:
     * - Updates trip status to cancelled
     * - Cancels all related bookings
     * - Processes refunds based on policy
     * - Sends notifications if requested
     * - Logs audit trail
     * 
     * @param Trip $trip Trip to cancel
     * @param array $data Cancellation data (reason, notify_passengers, refund_policy)
     * @return array Cancellation result with success status and message
     */
public function emergencyCancel(Trip $trip, array $data): array
{
    try {
        DB::beginTransaction();

        // Update trip status
        $trip->update([
            'trip_status_id' => 3, // Cancelled status
            'cancellation_reason' => $data['reason'],
            'cancelled_at' => now()
        ]);

        $totalRefunded = 0;
        $cancelledBookings = 0;
        $cancelledBookingIds = [];

        // Process bookings
        foreach ($trip->bookings as $booking) {
            $booking->update([
                'booking_status_id' => 3, // Cancelled
                'cancelled_at' => now(),
                'cancellation_reason' => 'Emergency trip cancellation: ' . $data['reason']
            ]);

            TripReservation::where('booking_id', $booking->id)
                ->update(['status' => 'cancelled']);

            // Process refund if needed
            if ($data['refund_policy'] !== 'none') {
                $refundAmount = $data['refund_policy'] === 'full' 
                    ? $booking->amount 
                    : $booking->amount * 0.5;

                \App\Models\Transaction::create([
                    'uuid' => Str::uuid(),
                    'user_id' => $booking->user_id,
                    'booking_id' => $booking->id,
                    'transaction_type' => 'refund',
                    'amount' => $refundAmount,
                    'description' => 'Emergency trip cancellation refund',
                    'status' => 'completed'
                ]);
            $totalRefunded += $refundAmount;

            }


            $cancelledBookings++;
            $cancelledBookingIds[] = $booking->id; // <-- هذا السطر كان داخل شرط if، أخرجناه للخارج
        } // <-- نهاية foreach

        // Log audit trail
        $this->auditService->log(
            $this->resolveActor(),
            'emergency_cancel',
            'trips',
            $trip->id,
            null,
            [
                'trip_uuid' => $trip->uuid,
                'reason' => $data['reason'],
                'refund_policy' => $data['refund_policy'],
                'affected_bookings' => $cancelledBookings,
                'total_refunded' => $totalRefunded
            ]
        );

        //  M-ACCT-002: Reverse accounting entries for each cancelled booking
        foreach ($cancelledBookingIds as $bookingId) {
            $booking = Booking::find($bookingId);
            if ($booking) {
                $this->reverseAccountingEntries($booking, $booking->amount);
            }
        }

        DB::commit();

        return [
            'success' => true,
            'message' => 'Trip cancelled successfully',
            'data' => [
                'cancelled_bookings' => $cancelledBookings,
                'total_refunded' => $totalRefunded
            ]
        ];

    } catch (\Exception $e) {
        DB::rollBack();
        
        Log::error('Emergency trip cancellation failed', [
            'trip_uuid' => $trip->uuid,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return [
            'success' => false,
            'message' => 'فشل في إلغاء الرحلة: ',
        ];
    }
}

    /**
     * Emergency trip reschedule with conflict handling
     * 
     * Reschedules a trip and handles conflicting bookings automatically
     * 
     * Business logic:
     * - Checks for booking conflicts with new time
     * - Auto-refunds conflicting bookings if requested
     * - Updates trip departure and arrival times
     * - Sends notifications to passengers
     * - Logs audit trail
     * 
     * @param Trip $trip Trip to reschedule
     * @param array $data Reschedule data (new_departure_time, reason, notify_passengers, auto_refund_conflicts)
     * @return array Reschedule result with success status and message
     */
    public function emergencyReschedule(Trip $trip, array $data): array
    {
        try {
            DB::beginTransaction();

            $newDepartureTime = \Carbon\Carbon::parse($data['new_departure_time']);
            $conflictingBookings = [];

            // Check for conflicts
            foreach ($trip->bookings as $booking) {
                $hasConflict = $booking->user->bookings()
                    ->where('booking_status_id', '!=', 3)
                    ->whereHas('trip', function($query) use ($newDepartureTime) {
                        $query->whereBetween('departure_time', [
                            $newDepartureTime->copy()->subHours(2),
                            $newDepartureTime->copy()->addHours(2)
                        ]);
                    })
                    ->exists();

                if ($hasConflict) {
                    $conflictingBookings[] = $booking;
                    
                    // Auto-refund conflicting bookings if requested
                    if ($data['auto_refund_conflicts']) {
                        $booking->update([
                            'booking_status_id' => 3,
                            'cancelled_at' => now(),
                            'cancellation_reason' => 'Auto-cancelled due to trip reschedule conflict'
                        ]);

                        \App\Models\Transaction::create([
                            'uuid' => Str::uuid(),
                            'user_id' => $booking->user_id,
                            'booking_id' => $booking->id,
                            'transaction_type' => 'refund',
                            'amount' => $booking->amount,
                            'description' => 'Auto-refund due to trip reschedule',
                            'status' => 'completed'
                        ]);
                    }
                }
            }

            // Update trip times
            $trip->update([
                'departure_time' => $newDepartureTime,
                'arrival_time' => $newDepartureTime->copy()->addHours(
                    $trip->arrival_time->diffInHours($trip->departure_time)
                ),
                'reschedule_reason' => $data['reason'],
                'rescheduled_at' => now()
            ]);



            // Log audit trail
            $this->auditService->log(
                $this->resolveActor(),
                'emergency_reschedule',
                'trips',
                $trip->id,
                null,
                [
                    'trip_uuid' => $trip->uuid,
                    'old_time' => $trip->getOriginal('departure_time'),
                    'new_time' => $newDepartureTime,
                    'reason' => $data['reason'],
                    'conflicting_bookings' => count($conflictingBookings),
                    'auto_refunded' => $data['auto_refund_conflicts'] ? count($conflictingBookings) : 0
                ]
            );

            DB::commit();

            return [
                'success' => true,
                'message' => 'Trip rescheduled successfully',
                'data' => [
                    'new_departure_time' => $newDepartureTime,
                    'conflicting_bookings' => count($conflictingBookings),
                    'auto_refunded' => $data['auto_refund_conflicts'] ? count($conflictingBookings) : 0
                ]
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Emergency trip reschedule failed', [
                'trip_uuid' => $trip->uuid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'فشل في إعادة جدولة الرحلة: '
            ];
        }
    }

    /**
     *  M-ACCT-002: Reverse accounting entries for emergency cancellation
     * 
     * Creates reversal entries for all original accounting transactions.
     * This ensures the accounting records reflect the cancellation.
     *
     * @param Booking $booking The booking being cancelled
     * @param float $refundAmount The refund amount
     * @return void
     */
    protected function reverseAccountingEntries(Booking $booking, float $refundAmount): void
    {
        $originalReference = TransactionReference::where('booking_id', $booking->id)->first();
        
        if (!$originalReference) {
            Log::warning('No original accounting reference found for booking', [
                'booking_uuid' => $booking->uuid,
                'booking_id' => $booking->id
            ]);
            return;
        }

        // Create new reference for reversal
        $reversalReference = TransactionReference::create([
            'uuid' => Str::uuid(),
            'reference_number' => 'EMG-' . strtoupper(Str::random(8)),
            'reference_type' => 'emergency_cancellation',
            'booking_id' => $booking->id,
            'description' => 'عكس قيد إلغاء طوارئ - حجز: ' . $booking->uuid,
        ]);

        // Get original transactions
        $originalTransactions = Transaction::where('transaction_reference_id', $originalReference->id)->get();

        foreach ($originalTransactions as $transaction) {
            // Reverse each transaction (debit becomes credit, credit becomes debit)
            $reversedType = $transaction->transaction_type === 'debit' ? 'credit' : 'debit';
            
            // Use original amount or prorated refund amount
            $reversalAmount = min($transaction->amount, $refundAmount);
            
            if ($reversalAmount > 0) {
                Transaction::create([
                    'transaction_reference_id' => $reversalReference->id,
                    'account_code' => $transaction->account_code,
                    'transaction_type' => $reversedType,
                    'amount' => $reversalAmount,
                    'description' => 'عكس: ' . $transaction->description
                ]);
            }
        }

        // Record refund transaction
        if ($refundAmount > 0) {
            // Debit: Reduce cash/liability
            Transaction::create([
                'transaction_reference_id' => $reversalReference->id,
                'account_code' => AccountCodes::LIABILITIES,
                'transaction_type' => 'debit',
                'amount' => $refundAmount,
                'description' => 'استرداد نقدي - إلغاء طوارئ: ' . $booking->uuid
            ]);
        }

        Log::info('Accounting entries reversed for emergency cancellation', [
            'booking_uuid' => $booking->uuid,
            'refund_amount' => $refundAmount,
            'original_reference_id' => $originalReference->id,
            'reversal_reference_id' => $reversalReference->id
        ]);
    }

    /**
     *  تحديد من يقوم بالعملية - يرفع exception لو غير مصدق
     * كان يقبل auth()->id() ?? 1 (ينسب للأدمن)
     */
    private function resolveActor(): int
    {
        $userId = auth()->id();
        if (!$userId) {
            throw new \RuntimeException('يجب تسجيل الدخول لتنفيذ هذه العملية. لا يمكن تنفيذها كـ system.');
        }
        return $userId;
    }
}
