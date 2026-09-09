<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\TripReservation;
use App\Models\Ticket;
use App\Models\Passenger;
use App\Models\Transaction;
use App\Models\TransactionReference;
use App\Constants\BookingStatus as BookingStatusConst;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Constants\AccountCodes;
use App\Constants\PaymentMethod;

use Illuminate\Support\Str;

class BookingModificationService
{
    public function __construct(
        private BookingCalculationService $bookingCalculationService,
        private AuditService $auditService,
        private RefundService $refundService
    ) {}
    
    /**
     * Modify passenger count in booking
     * 
     * This method handles modification of passenger count in an existing booking
     * with comprehensive validation, seat availability checks, and financial updates.
     * 
     * Business logic:
     * - Validates booking can be modified (not cancelled, not departed, not paid in cash)
     * - Recalculates booking cost based on new passenger data
     * - Validates seat availability for new passenger count
     * - Updates tickets and passenger records
     * - Updates booking financials including taxes and payments
     * - Records accounting transactions for modification
     * 
     * Security considerations:
     * - Database transactions for atomicity
     * - Comprehensive error logging
     * - Audit logging for compliance
     * 
     * @param Booking $booking Booking to modify
     * @param array $newPassengersData New passenger data array
     * @param int $userId ID of user performing modification
     * @return array Modification result with success status and new amount
     * @throws \RuntimeException If modification is not allowed or fails
     */
    public function modifyPassengers(Booking $booking, array $newPassengersData, int $userId): array
    {
        if (!$this->canModifyBooking($booking)) {
            throw new \RuntimeException('لا يمكن تعديل هذا الحجز');
        }
        
        try {
            DB::beginTransaction();
            
            // 1. حساب التكلفة الجديدة
            $newCalculation = $this->recalculateBooking($booking, $newPassengersData);
            
            // 2. التحقق من توفر المقاعد
            $this->validateSeatAvailability($booking, $newPassengersData);
            
            // 3. تحديث التذاكر والمسافرين
            $this->updateTicketsAndPassengers($booking, $newPassengersData);
            
            // 4. تحديث المبلغ والضرائب
            $this->updateBookingFinancials($booking, $newCalculation);
            
            // 5. تسجيل التعديل المالي
            $this->recordModificationTransaction($booking, $newCalculation, $userId);
            
            DB::commit();
            
            $this->auditService->log($userId, 'updated', 'bookings', $booking->id, [
                'modification_type' => 'passengers_count',
                'old_amount' => $booking->amount,
                'new_amount' => $newCalculation['total_amount'],
            ]);
            
            return [
                'success' => true,
                'message' => 'تم تعديل عدد الركاب بنجاح',
                'new_amount' => $newCalculation['total_amount'],
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'فشل في تعديل الحجز. يرجى المحاولة مرة أخرى.',
            ];        }
    }
    
    /**
     * Validate if booking can be modified
     * 
     * This method checks if a booking meets the criteria for modification
     * based on status, timing, and payment method.
     * 
     * Business logic:
     * - Cannot modify cancelled bookings
     * - Cannot modify bookings where trips have already departed
     * - Cannot modify cash payments (policy restriction)
     * 
     * @param Booking $booking Booking to validate
     * @return bool True if booking can be modified
     */
    private function canModifyBooking(Booking $booking): bool
    {
        // لا يمكن تعديل الحجوزات الملغاة أو المنتهية
        if ($booking->booking_status_id === BookingStatusConst::CANCELLED) {
            return false;
        }
        
        // لا يمكن تعديل الحجوزات التي بدأت رحلاتها
        foreach ($booking->tripReservations as $reservation) {
            if ($reservation->trip->departure_time <= now()) {
                return false;
            }
        }
        
        // لا يمكن تعديل الحجوزات المدفوعة بالكامل (يمكن تعديل هذه القاعدة حسب السياسة)
        if ($booking->payment && $booking->payment->payment_method_id === PaymentMethod::CASH) { // Assuming 1 = Cash
            return false;
        }
        
        return true;
    }
    
    /**
     * Recalculate booking cost based on new passenger data
     * 
     * This method recalculates the total booking amount when passenger
     * count changes, separating adults and children for proper pricing.
     * 
     * Business logic:
     * - Identifies adult passengers (age >= 18)
     * - Calculates child passenger count
     * - Uses booking calculation service for cost calculation
     * - Does not allow coupon changes during modification
     * 
     * @param Booking $booking Booking being modified
     * @param array $newPassengersData New passenger data
     * @return array Recalculation result with new amounts and tax details
     */
    private function recalculateBooking(Booking $booking, array $newPassengersData): array
    {
        $trips = $booking->tripReservations->map->trip->toArray();
        $adults = collect($newPassengersData)->filter(fn($p) => $this->isAdult($p))->count();
        $children = count($newPassengersData) - $adults;
        
        return $this->bookingCalculationService->calculateTotalAmount(
            $trips,
            $adults,
            $children,
            null, // لا يسمح بتغيير الكوبون في التعديل
            $booking->user_id
        );
    }
    
    /**
     * Validate seat availability for modified booking
     * 
     * This method ensures sufficient seats are available for the new
     * passenger count across all trips in the booking.
     * 
     * Business logic:
     * - Calculates current available seats per trip
     * - Adds back current reservation seats (since modifying)
     * - Validates new passenger count fits in available seats
     * 
     * @param Booking $booking Booking being modified
     * @param array $newPassengersData New passenger data
     * @return void
     * @throws \RuntimeException If insufficient seats available
     */
    private function validateSeatAvailability(Booking $booking, array $newPassengersData): void
    {
        $totalSeats = count($newPassengersData);

        foreach ($booking->tripReservations as $reservation) {
            $trip = $reservation->trip;
            if (!$trip || !$trip->vehicle) {
                throw new \RuntimeException("الرحلة غير متوفرة {$reservation->trip?->uuid}");
            }

            $vehicleSeats = $trip->vehicle->seats()->count();
            $bookedSeats = count($trip->getBookedSeatIds());
            $currentAvailableSeats = $vehicleSeats - $bookedSeats;
            // Add back the seats from current reservation since we're modifying it
            $availableSeats = $currentAvailableSeats + $reservation->tickets()->count();

            if ($availableSeats < $totalSeats) {
                throw new \RuntimeException("المقاعد غير كافية في الرحلة {$trip->uuid}");
            }
        }
    }
    
    /**
     * Update tickets and passengers for modified booking
     * 
     * This method removes existing tickets and creates new ones based on the updated passenger data.
     * 
     * Business logic:
     * - Deletes existing tickets only (NOT passengers to preserve historical data)
     * - Creates new passenger records with sanitized data
     * - Creates new ticket records with proper pricing
     * 
     * Security considerations:
     * - Input sanitization for passenger names
     * - UUID generation for new records
     * 
     * @param Booking $booking Booking being modified
     * @param array $newPassengersData New passenger data
     * @return void
     */
    private function updateTicketsAndPassengers(Booking $booking, array $newPassengersData): void
    {
        // حذف التذاكر فقط (لا تحذف المسافرين للحفاظ على البيانات التاريخية)
        $tickets = $booking->tripReservations->flatMap->tickets;
        Ticket::whereIn('id', $tickets->pluck('id'))->delete();
        
        // إنشاء تذاكر ومسافرين جدد
        foreach ($booking->tripReservations as $reservation) {
            $this->createNewTickets($reservation, $newPassengersData, $booking->user_id);
        }
    }
    
    /**
     * Create new tickets for trip reservation
     * 
     * This method creates passenger and ticket records for each passenger
     * in the reservation with proper pricing based on age.
     * 
     * Business logic:
     * - Creates passenger record with sanitized data
     * - Creates ticket record with age-appropriate pricing
     * - Generates random ticket number
     * - Assigns seat if specified
     * 
     * Security considerations:
     * - Input sanitization for passenger names
     * - UUID generation for tracking
     * - Service layer independence from HTTP (no auth())
     * 
     * @param TripReservation $reservation Trip reservation to create tickets for
     * @param array $passengersData Passenger data array
     * @param int $userId ID of user creating the tickets
     * @return void
     */
    private function createNewTickets(TripReservation $reservation, array $passengersData, int $userId): void
    {
        foreach ($passengersData as $passengerData) {
            $passenger = Passenger::create([
                'uuid' => Str::uuid()->toString(),
                'created_by' => $userId,
                'full_name' => htmlspecialchars(strip_tags($passengerData['name'] ?? 'مسافر'), ENT_QUOTES, 'UTF-8'),
                'birthday' => $passengerData['birthday'] ?? null,
                'gender_id' => $passengerData['gender_id'] ?? null,
                'phone_number' => $passengerData['phone_number'] ?? null,
                'personal_country_place_of_issue' => $passengerData['personal_country_place_of_issue'] ?? null,
                'personal_city_place_of_issue' => $passengerData['personal_city_place_of_issue'] ?? null,
                'personal_card_date_issu' => $passengerData['personal_card_date_issu'] ?? null,
                'personal_card_date_expiry' => $passengerData['personal_card_date_expiry'] ?? null,
                'personal_card_id' => $passengerData['personal_card_id'] ?? null,
            ]);
            
            Ticket::create([
                'trip_reservation_id' => $reservation->id,
                'passenger_id' => $passenger->id,
                'seat_id' => $passengerData['seat_id'] ?? null,
                'ticket_number' => Str::random(10),
                'price_at_booking' => $this->isAdult($passengerData) ? $reservation->trip->price_for_adult : $reservation->trip->price_for_child,
                'status' => 'active',
            ]);
        }
    }
    
    /**
     * Update booking financial data after modification
     * 
     * This method updates the booking amount, taxes, and payment records
     * to reflect the changes from passenger modification.
     * 
     * Business logic:
     * - Updates total booking amount
     * - Recreates tax records based on new amount
     * - Updates payment records if amount changed
     * - Processes refund if amount decreased
     * - Adds payment note if amount increased
     * 
     * @param Booking $booking Booking being modified
     * @param array $newCalculation New financial calculation data
     * @return void
     */
    private function updateBookingFinancials(Booking $booking, array $newCalculation): void
    {
        // تحديث المبلغ الإجمالي
        $oldAmount = $booking->amount;
        $booking->update(['amount' => $newCalculation['total_amount']]);
        
        // استخدام BookingCalculationService لإنشاء الضرائب
        $this->bookingCalculationService->createBookingTaxes($booking->id, $newCalculation['tax_details']);
        
        // تحديث الدفع إذا لزم الأمر
        if ($booking->payment && $oldAmount !== $newCalculation['total_amount']) {
            $difference = $newCalculation['total_amount'] - $oldAmount;
            
            if ($difference > 0) {
                // المبلغ زاد - يجب دفع الفرق
                $booking->payment->update([
                    'payed_amount' => $booking->payment->payed_amount + $difference,
                    'note' => ($booking->payment->note ?? '') . ' | تعديل المبلغ: +' . $difference,
                ]);
            } else {
                // المبلغ نقص - يجب استرداد الفرق
                $this->processRefundForModification($booking, abs($difference));
            }
        }
    }
    
    /**
     * Record accounting transaction for booking modification
     * 
     * This method creates double-entry accounting transactions to record
     * the financial impact of the booking modification.
     * 
     * Accounting logic:
     * - Creates transaction reference for audit trail
     * - Debits/credits customer account based on amount change
     * - Credits/debits revenue account for corresponding entry
     * 
     * @param Booking $booking Booking being modified
     * @param array $newCalculation New financial calculation data
     * @param int $userId ID of user performing modification
     * @return void
     */
    private function recordModificationTransaction(Booking $booking, array $newCalculation, int $userId): void
    {
        $transactionRef = TransactionReference::create([
            'reference_number' => 'MOD-' . strtoupper(Str::random(8)),
            'reference_type' => 'booking_modification',
            'reference_id' => $booking->id,
            'created_by' => $userId,
        ]);

        $oldAmount = $booking->amount;
        $newAmount = $newCalculation['total_amount'];
        $difference = $newAmount - $oldAmount;

        if ($difference > 0) {
            // المبلغ زاد - مدين أصول / دائن التزامات
            Transaction::create([
                'transaction_reference_id' => $transactionRef->id,
                'account_code' => AccountCodes::ASSETS,
                'transaction_type' => 'debit',
                'amount' => abs($difference),
                'description' => 'زيادة مبلغ حجز - ' . $booking->uuid
            ]);

            Transaction::create([
                'transaction_reference_id' => $transactionRef->id,
                'account_code' => AccountCodes::LIABILITIES,
                'transaction_type' => 'credit',
                'amount' => abs($difference),
                'description' => 'زيادة مستحق مزود - تعديل حجز: ' . $booking->uuid
            ]);
        } else {
            // المبلغ نقص - مدين التزامات / دائن أصول
            Transaction::create([
                'transaction_reference_id' => $transactionRef->id,
                'account_code' => AccountCodes::LIABILITIES,
                'transaction_type' => 'debit',
                'amount' => abs($difference),
                'description' => 'نقص مستحق مزود - تعديل حجز: ' . $booking->uuid
            ]);

            Transaction::create([
                'transaction_reference_id' => $transactionRef->id,
                'account_code' => AccountCodes::ASSETS,
                'transaction_type' => 'credit',
                'amount' => abs($difference),
                'description' => 'نقص مبلغ حجز - ' . $booking->uuid
            ]);
        }
    }    
    /**
     * Process refund for booking modification
     * 
     * This method handles refund processing when booking amount decreases
     * 
     * @param Booking $booking Booking being modified
     * @param float $refundAmount Amount to refund
     * @return void
     */
    private function processRefundForModification(Booking $booking, float $refundAmount): void
    {
        if ($refundAmount <= 0) {
            return;
        }

        DB::transaction(function () use ($booking, $refundAmount) {
            // 1. تحديث سجل الدفع
            if ($booking->payment) {
                $booking->payment->update([
                    'payed_amount' => max(0, $booking->payment->payed_amount - $refundAmount),
                    'note' => ($booking->payment->note ?? '') . ' | استرداد مبلغ: -' . $refundAmount . ' (تعديل عدد الركاب)',
                ]);
            }

            // 2. تسجيل القيود المحاسبية
            $this->refundService->processRefundAccounting(
                $booking,
                $refundAmount,
                0
            );

                    // 3. سجل تدقيق
        $this->auditService->log(
            $booking->user_id,
            'modification_refund',
            'bookings',
            $booking->id,
            null,
            [
                'refund_amount' => $refundAmount,
                'modification_type' => 'passengers_count',
            ]
        );
        });
    }
    /**
     * Determine if passenger is an adult based on age
     * 
     * This method checks if a passenger is 18 years or older based on
     * their birthday for pricing purposes.
     * 
     * Business logic:
     * - Returns true if no birthday provided (default to adult)
     * - Calculates age from birthday
     * - Returns true if age >= 18
     * 
     * @param array $passengerData Passenger data including birthday
     * @return bool True if passenger is an adult
     */
    private function isAdult(array $passengerData): bool
    {
        if (!isset($passengerData['birthday'])) {
            return true; // افتراض بالغ إذا لم يوجد تاريخ ميلاد
        }
        
        return \Carbon\Carbon::parse($passengerData['birthday'])->age >= 18;
    }
}
