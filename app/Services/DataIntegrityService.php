<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Trip;
use App\Models\Ticket;
use App\Models\Transaction;
use App\Constants\BookingStatus as BookingStatusConst;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Data Integrity and Validation Service
 * 
 * This service provides comprehensive validation for data integrity,
 * business logic rules, and accounting constraints across the system.
 * 
 * Security features:
 * - Comprehensive data validation before persistence
 * - Status transition validation for state machines
 * - Accounting balance verification
 * - Seat availability validation with locking
 * - Financial data precision checks
 */
class DataIntegrityService
{
    /**
     * Validate booking data before persistence
     * 
     * This method validates booking data for business rule compliance
     * and data integrity before saving to the database.
     * 
     * Business logic:
     * - Validates passenger counts (adults > 0, children >= 0)
     * - Validates total passengers > 0
     * - Validates amount is positive and within reasonable limits
     * - Validates prices are positive (adult) or non-negative (child)
     * - Validates discount percentage is between 0-100
     * 
     * @param array $data Booking data to validate
     * @return array Validation errors (empty if valid)
     */
    public function validateBookingData(array $data): array
    {
        $errors = [];

        // التحقق من عدد الركاب
        if (!isset($data['adults']) || $data['adults'] <= 0) {
            $errors['adults'] = 'عدد البالغين يجب أن يكون أكبر من صفر';
        }

        if (isset($data['children']) && $data['children'] < 0) {
            $errors['children'] = 'عدد الأطفال لا يمكن أن يكون سالباً';
        }

        $totalPassengers = ($data['adults'] ?? 0) + ($data['children'] ?? 0);
        if ($totalPassengers <= 0) {
            $errors['total_passengers'] = 'إجمالي عدد الركاب يجب أن يكون أكبر من صفر';
        }

        // التحقق من المبلغ الإجمالي (الأهم)
        if (isset($data['amount'])) {
            if ($data['amount'] <= 0) {
                $errors['amount'] = 'المبلغ الإجمالي يجب أن يكون موجباً';
            }
            if ($data['amount'] > 999999999) {
                $errors['amount'] = 'المبلغ الإجمالي كبير جداً';
            }
        }

        // التحقق من الأسعار (للتوافق مع الكود القديم)
        if (isset($data['price_for_adult']) && $data['price_for_adult'] <= 0) {
            $errors['price_for_adult'] = 'سعر البالغ يجب أن يكون موجباً';
        }

        if (isset($data['price_for_child']) && $data['price_for_child'] < 0) {
            $errors['price_for_child'] = 'سعر الطفل لا يمكن أن يكون سالباً';
        }

        // التحقق من الخصم
        if (isset($data['discount_percentage']) && ($data['discount_percentage'] < 0 || $data['discount_percentage'] > 100)) {
            $errors['discount_percentage'] = 'نسبة الخصم يجب أن تكون بين 0 و 100';
        }

        return $errors;
    }

    /**
     * Validate status transition logic
     * 
     * This method ensures that status transitions follow the defined
     * state machine rules and business logic constraints.
     * 
     * Business logic:
     * - Cannot transition from cancelled to completed directly
     * - Cannot transition from completed to cancelled without admin approval
     * - Validates against defined valid transition matrix
     * 
     * @param int $currentStatus Current booking status ID
     * @param int $newStatus Target booking status ID
     * @return array Validation errors (empty if valid)
     */
    public function validateStatusTransition(int $currentStatus, int $newStatus): array
    {
        $errors = [];

        // لا يمكن الانتقال من ملغى إلى مكتمل مباشرة
        if ($currentStatus === BookingStatusConst::CANCELLED && 
            $newStatus === BookingStatusConst::COMPLETED) {
            $errors['status_transition'] = 'لا يمكن تحويل حجز ملغى إلى مكتمل مباشرة';
        }

        // لا يمكن الانتقال من مكتمل إلى ملغى بدون إذن خاص
        if ($currentStatus === BookingStatusConst::COMPLETED && 
            $newStatus === BookingStatusConst::CANCELLED) {
            $errors['status_transition'] = 'لا يمكن إلغاء حجز مكتمل بدون موافقة إدارية';
        }

        // التحقق من انتقالات الحجز
        $validTransitions = [
            BookingStatusConst::PENDING => [
                BookingStatusConst::CONFIRMED,
                BookingStatusConst::CANCELLED,
                BookingStatusConst::CANCELLED_BY_USER,
                BookingStatusConst::CANCELLED_BY_ADMIN,
            ],
            BookingStatusConst::CONFIRMED => [
                BookingStatusConst::COMPLETED,
                BookingStatusConst::CANCELLED,
                BookingStatusConst::CANCELLED_BY_USER,
                BookingStatusConst::CANCELLED_BY_ADMIN,
            ],
            BookingStatusConst::CANCELLED => [],
            BookingStatusConst::CANCELLED_BY_USER => [],
            BookingStatusConst::CANCELLED_BY_ADMIN => [],
            BookingStatusConst::COMPLETED => [],
        ];

        if (isset($validTransitions[$currentStatus]) && 
            !in_array($newStatus, $validTransitions[$currentStatus])) {
            $errors['status_transition'] = 'انتقال الحالة غير مسموح';
        }

        return $errors;
    }

    /**
     * Validate accounting balance for double-entry compliance
     * 
     * This method verifies that accounting transactions maintain
     * double-entry bookkeeping principles (debits = credits).
     * 
     * Business logic:
     * - Validates debits equal credits (within 0.01 tolerance for rounding)
     * - Validates no zero or negative amounts
     * - Validates amounts are within reasonable limits
     * 
     * Security considerations:
     * - Prevents accounting data corruption
     * - Ensures financial data integrity
     * 
     * @param int $transactionRefId Transaction reference ID to validate
     * @return array Validation errors (empty if valid)
     */
    public function validateAccountingBalance(int $transactionRefId): array
    {
        $errors = [];

        try {
            // التحقق من أن مجموع الديون equals مجموع الدائنات
            $debits = Transaction::where('transaction_reference_id', $transactionRefId)
                ->where('transaction_type', 'debit')
                ->sum('amount');

            $credits = Transaction::where('transaction_reference_id', $transactionRefId)
                ->where('transaction_type', 'credit')
                ->sum('amount');

            if (abs($debits - $credits) > 0.01) { // السماح بفرق بسيط بسبب التقريب
                $errors['accounting_balance'] = 'عدم توازن في الحسابات: الديون = ' . $debits . '، الدائنات = ' . $credits;
            }

            // التحقق من عدم وجود معاملات بمبلغ صفر أو سالب
            $invalidTransactions = Transaction::where('transaction_reference_id', $transactionRefId)
                ->where(function ($query) {
                    $query->where('amount', '<=', 0)
                          ->orWhere('amount', '>', 999999999);
                })
                ->count();

            if ($invalidTransactions > 0) {
                $errors['invalid_amounts'] = 'يوجد معاملات بمبالغ غير صالحة';
            }

        } catch (\Exception $e) {
            $errors['validation_error'] = 'خطأ في التحقق من الحسابات: ';
        }

        return $errors;
    }

    /**
     * Validate seat availability with database locking
     * 
     * This method checks if sufficient seats are available for booking
     * while preventing race conditions through database locking.
     * 
     * Business logic:
     * - Validates trip exists and has vehicle
     * - Calculates available seats (total - booked)
     * - Validates requested seats > 0
     * - Validates sufficient seats available
     * - Validates trip hasn't departed yet
     * 
     * Security considerations:
     * - Database locking (lockForUpdate) prevents race conditions
     * - Prevents overbooking
     * 
     * @param int $tripId Trip ID to validate
     * @param int $requestedSeats Number of seats requested
     * @return array Validation errors (empty if valid)
     */
    public function validateSeatAvailability(int $tripId, int $requestedSeats): array
    {
        $errors = [];

        try {
            $trip = Trip::lockForUpdate()->find($tripId);
            
            if (!$trip || !$trip->vehicle) {
                $errors['trip_not_found'] = 'الرحلة غير موجودة أو لا تحتوي على مركبة';
                return $errors;
            }

            $totalSeats = $trip->vehicle->seats()->count();
            $bookedSeats = count($trip->getBookedSeatIds());
            $availableSeats = $totalSeats - $bookedSeats;
            
            if ($requestedSeats <= 0) {
                $errors['invalid_seats'] = 'عدد المقاعد المطلوب يجب أن يكون أكبر من صفر';
            }

            if ($requestedSeats > $availableSeats) {
                $errors['insufficient_seats'] = 'المقاعد غير كافية. المتاح: ' . $availableSeats . '، المطلوب: ' . $requestedSeats;
            }

            // التحقق من أن الرحلة لم تبدأ بعد
            if ($trip->departure_time <= now()) {
                $errors['trip_started'] = 'الرحلة بدأت بالفعل، لا يمكن الحجز';
            }

        } catch (\Exception $e) {
            $errors['validation_error'] = 'خطأ في التحقق من المقاعد: ';
        }

        return $errors;
    }

    /**
     * Validate financial data integrity
     * 
     * This method validates financial amounts for precision,
     * positivity, and context-specific limits.
     * 
     * Business logic:
     * - Validates amount is present and not null
     * - Validates amount is positive
     * - Validates amount is within reasonable limits
     * - Validates decimal precision (max 2 decimal places)
     * - Validates against context-specific max amounts
     * 
     * @param float $amount Amount to validate
     * @param array $context Additional context for validation (e.g., max_amount)
     * @return array Validation errors (empty if valid)
     */
    public function validateFinancialData(float $amount, array $context = []): array
    {
        $errors = [];

        // التحقق من المبلغ
        if (!isset($amount) || $amount === null) {
            $errors['amount'] = 'المبلغ مطلوب';
            return $errors;
        }

        if ($amount <= 0) {
            $errors['amount'] = 'المبلغ يجب أن يكون موجباً';
        }

        if ($amount > 999999999) {
            $errors['amount'] = 'المبلغ كبير جداً';
        }

        // التحقق من عدد المنازل العشرية
        if (strlen(substr(strrchr((string)$amount, "."), 1)) > 2) {
            $errors['decimal_precision'] = 'المبلغ يجب أن يحتوي على منزلتين عشريتين كحد أقصى';
        }

        // التحقق من السياق المالي
        if (isset($context['max_amount']) && $amount > $context['max_amount']) {
            $errors['amount_exceeds_limit'] = 'المبلغ يتجاوز الحد المسموح';
        }

        return $errors;
    }

    /**
     * Perform comprehensive validation before data persistence
     * 
     * This method runs all relevant validations based on operation type
     * to ensure data integrity before saving to the database.
     * 
     * Business logic:
     * - Runs booking data validation for booking operations
     * - Runs financial data validation if amount present
     * - Runs status transition validation if status change
     * 
     * @param array $data Data to validate
     * @param string $operation Type of operation (booking, etc.)
     * @return array All validation errors (empty if valid)
     */
    public function comprehensiveValidation(array $data, string $operation): array
    {
        $allErrors = [];

        // التحقق من بيانات الحجز
        if ($operation === 'booking') {
            $allErrors = array_merge($allErrors, $this->validateBookingData($data));
        }

        // التحقق من البيانات المالية
        if (isset($data['amount'])) {
            $allErrors = array_merge($allErrors, $this->validateFinancialData($data['amount'], $data));
        }

        // التحقق من انتقالات الحالة
        if (isset($data['current_status']) && isset($data['new_status'])) {
            $allErrors = array_merge($allErrors, $this->validateStatusTransition($data['current_status'], $data['new_status']));
        }

        return $allErrors;
    }
    // app/Services/DataIntegrityService.php

/**
 * التحقق من صحة انتقال حالة الحجز باستخدام uuid
 *
 * @param  string $uuid
 * @param  mixed  $newStatusId
 * @return array
 */
    public function validateBookingStatusTransition(string $uuid, $newStatusId): array
    {
        $booking = \App\Models\Booking::where('uuid', $uuid)->first();

        if (!$booking) {
            return ['booking_uuid' => 'الحجز غير موجود'];
        }

        return $this->validateStatusTransition(
            $booking->booking_status_id,
            $newStatusId
        );
    }
}
