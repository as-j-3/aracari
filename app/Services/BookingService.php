<?php

namespace App\Services;

use App\Constants\AccountCodes;
use App\Constants\BookingStatus;
use App\Constants\NotificationTypes;
use App\Constants\PaymentMethod;
use App\Constants\TicketStatus;
use App\Constants\TransactionReferenceStatus;
use App\Constants\TripSeatStatus;
use App\Constants\WeekDays;
use App\DTOs\BookTripDTO;
use App\DTOs\InitiateBookingDTO;
use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\WalletDisabledException;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\Passenger;
use App\Models\Policy;
use App\Models\PolicyCancellationRule;
use App\Models\Seat;
use App\Models\Ticket;
use App\Models\Transaction;
use App\Models\TransactionReference;
use App\Models\Trip;
use App\Models\TripReservation;
use App\Models\TripSeat;
use App\Models\User;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Repositories\Contracts\PassengerRepositoryInterface;
use App\Repositories\Contracts\TicketRepositoryInterface;
use App\Repositories\Contracts\TransactionRepositoryInterface;
use App\Repositories\Contracts\TripRepositoryInterface;
use App\Services\Wallet\WalletService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BookingService
{
    public function __construct(
        private TripRepositoryInterface $tripRepository,
        private CouponService $couponService,
        private BookingCalculationService $bookingCalculationService,
        private CommissionService $commissionService,
        private BranchService $branchService,
        private AuditService $auditService,
        private DataIntegrityService $dataIntegrityService,
        private RefundService $refundService,
        private CurrencyService $currencyService,
        private ExchangeRateService $exchangeRateService,
        private NotificationService $notificationService,
        private BookingRepositoryInterface $bookingRepository,
        public TransactionRepositoryInterface $transactionRepository,
        private PassengerRepositoryInterface $passengerRepository,
        private TicketRepositoryInterface $ticketRepository,
        private WalletService $walletService,
        private PaymentGatewayService $paymentGatewayService,
        private TripChildFactory $childFactory
    ) {}

    /**
     * حجز رحلة واحدة (ذهاب فقط)
     */
    public function bookTrip(BookTripDTO $dto): array
    {
        $actor = auth()->user();

        //  المستخدم يحجز لنفسه = مسموح دائماً (بدون صلاحية)
        if ($actor && (int) $actor->id === (int) $dto->userId) {
            return $this->bookTrips($dto);
        }

        //  الأدمن أو المزود يحجز لشخص آخر = يحتاج صلاحية
        if ($actor && (int) $actor->id !== (int) $dto->userId && ! $actor->hasPermission('bookings:create')) {
            return $this->error('غير مصرح لك بإنشاء حجز');
        }

        return $this->bookTrips($dto);
    }

    /**
     * المرحلة الأولى: بدء الحجز وقفل المقاعد مؤقتاً (Hold) لمدة دقيقتين
     * يستقبل بيانات الركاب ويفحص الرحلة، ويقفل المقاعد المحددة أو يختار تلقائياً للركاب الذين لم يحددوا مقاعد.
     */
    public function initiateBooking(InitiateBookingDTO $dto): array
    {
        $actor = auth()->user();
        if ($actor && (int) $actor->id !== (int) $dto->userId && ! $actor->hasPermission('bookings:create')) {
            return $this->error('غير مصرح لك بإنشاء حجز');
        }

        $totalPassengersCount = $dto->adults + $dto->children;
        if ($totalPassengersCount <= 0) {
            return $this->error('يجب تحديد عدد المسافرين');
        }

        if (! empty($dto->passengersData) && count($dto->passengersData) !== $totalPassengersCount) {
            return $this->error('عدد المسافرين المرسل لا يتطابق مع إجمالي البالغين والأطفال');
        }

        $trip = $this->tripRepository->findByUuid($dto->tripUuid);
        if (! $trip) {
            return $this->error("الرحلة {$dto->tripUuid} غير موجودة");
        }

        // JIT Child Trip Generation if departure date specified
        if ($dto->departureDate) {
            $targetDate = Carbon::parse($dto->departureDate)->startOfDay();
            $parentTripDepartureDate = Carbon::parse($trip->departure_time)->startOfDay();

            if ($targetDate->gt($parentTripDepartureDate)) {
                $existingChildTrip = Trip::where('parent_id', $trip->id)
                    ->whereDate('departure_time', $targetDate)
                    ->first();

                if ($existingChildTrip) {
                    $trip = $existingChildTrip;
                } else {
                    $trip = $this->spawnTripFromParent($trip, $targetDate);
                    if (! $trip) {
                        return $this->error("هذه الرحلة غير متاحة في التاريخ المطلوب ({$targetDate->toDateString()})");
                    }
                }
            }
        }

        // Permissions & Time Checks
        if (! $trip->is_public && $trip->user_id !== $dto->userId && ! auth()->user()?->hasPermission('trips:book-any')) {
            return $this->error("الرحلة {$trip->uuid} غير متاحة للحجز");
        }

        if ($trip->departure_time <= now()) {
            return $this->error("الرحلة {$trip->uuid} قد بدأت بالفعل، لا يمكن حجزها");
        }

        $bookingDeadline = $trip->departure_time->subHours(2);
        if (now() > $bookingDeadline) {
            return $this->error("انتهى وقت حجز الرحلة {$trip->uuid}");
        }

        // Financial Calculation
        $baseBookingAmount = ($dto->adults * $trip->price_for_adult) + ($dto->children * $trip->price_for_child);
        $appliedCoupon = null;
        $discountAmount = 0;

        if ($dto->couponCode) {
            $appliedCoupon = $this->couponService->findValidCoupon($dto->couponCode);
            if (! $appliedCoupon) {
                return $this->error('الكوبون غير صالح أو منتهي الصلاحية');
            }

            $applicableTrips = $this->couponService->getCouponApplicableTrips($appliedCoupon, [$trip]);
            if (empty($applicableTrips)) {
                return $this->error('هذا الكوبون لا ينطبق على الرحلة المختارة');
            }

            $discountedAmount = $this->couponService->applyDiscount($baseBookingAmount, $appliedCoupon);
            $discountAmount = $baseBookingAmount - $discountedAmount;
        }

        $financialCalculation = $this->bookingCalculationService->calculateBookingCosts($baseBookingAmount, $appliedCoupon, [$trip], $discountAmount);
        $finalBookingAmount = $financialCalculation['total_amount'];

        $baseCurrencyId = $this->currencyService->baseCurrencyId();
        $paymentCurrencyId = $dto->currencyId ?? $baseCurrencyId;
        $exchangeRate = $this->exchangeRateService->getRate($paymentCurrencyId, $baseCurrencyId);
        if (! $exchangeRate || $exchangeRate <= 0) {
            return $this->error('سعر الصرف غير متاح للعملة المختارة');
        }

        $baseAmount = $financialCalculation['base_amount'];
        $paymentAmount = round($finalBookingAmount * $exchangeRate, 2);

        // Branch & Commission
        $branch = $this->branchService->getBranchByTrip($trip);
        $bookingBranchId = $branch?->id;
        $bookingCommission = $this->commissionService->calculateBookingCommission($financialCalculation['taxable_amount'], $bookingBranchId);

        try {
            DB::beginTransaction();

            $lockedTrip = $this->tripRepository->lockForUpdate($trip->id);
            if (! $lockedTrip) {
                throw new \Exception("فشل قفل الرحلة {$trip->uuid}");
            }

            $availableSeats = $lockedTrip->available_seats
                ?? ($lockedTrip->vehicle->seats()->count() - count($lockedTrip->getBookedSeatIds()));

            if ($availableSeats < $totalPassengersCount) {
                throw new \Exception("المقاعد المتاحة لا تكفي في الرحلة {$trip->uuid}");
            }

            // إنشاء سجل الحجز بحالة PENDING
            $bookingRecord = $this->bookingRepository->create([
                'uuid' => Str::uuid()->toString(),
                'user_id' => $dto->userId,
                'branch_id' => $bookingBranchId,
                'booking_status_id' => BookingStatus::PENDING,
                'amount' => $paymentAmount,
                'currency_id' => $paymentCurrencyId,
                'exchange_rate' => $exchangeRate,
                'base_amount' => $baseAmount,
                'commission_id' => $bookingCommission['id'],
                'commission_value' => $bookingCommission['value'],
                'commission_amount' => $bookingCommission['amount'],
                'commission_type_fixed_or_percentage' => $bookingCommission['type'] ?? 'percentage',
                'note' => $dto->couponCode ? 'كوبون: '.$dto->couponCode : null,
            ]);

            // حفظ الضرائب على سجل الحجز
            $this->bookingCalculationService->createBookingTaxes($bookingRecord->id, $financialCalculation['tax_details'], $paymentCurrencyId, $exchangeRate);

            // إنشاء حجز الرحلة TripReservation
            $tripReservationRecord = TripReservation::create([
                'booking_id' => $bookingRecord->id,
                'trip_id' => $lockedTrip->id,
                'direction' => null,
                'price_at_booking' => $baseBookingAmount,
                'status' => 'active',
            ]);

            // مدة الهولد: دقيقتين كما طلب المستخدم
            $durationMinutes = $dto->holdDurationMinutes;
            $reservedUntil = now()->addMinutes($durationMinutes);
            $tripVehicleId = $lockedTrip->vehicle_id;
            $currentlyBookedSeatIds = $lockedTrip->getBookedSeatIds();
            $individualTicketPrice = $baseBookingAmount / $totalPassengersCount;

            $heldSeatsSummary = [];
            $passengersSummary = [];

            foreach ($dto->passengersData as $pData) {
                $fullName = htmlspecialchars(strip_tags($pData['name'] ?? 'مسافر'), ENT_QUOTES, 'UTF-8');

                $passengerRecord = $this->passengerRepository->findOrCreate([
                    'uuid' => Str::uuid()->toString(),
                    'created_by' => $dto->userId,
                    'full_name' => $fullName,
                    'birthday' => $pData['birthday'] ?? null,
                    'gender_id' => $pData['gender_id'] ?? null,
                    'phone_number' => $pData['phone_number'] ?? null,
                    'personal_card_id' => $pData['personal_card_id'] ?? null,
                    'personal_card_id_photo' => $pData['personal_card_id_photo'] ?? null,
                    'personal_country_place_of_issue' => $pData['personal_country_place_of_issue'] ?? null,
                    'personal_city_place_of_issue' => $pData['personal_city_place_of_issue'] ?? null,
                    'personal_card_date_issu' => $pData['personal_card_date_issu'] ?? null,
                    'personal_card_date_expiry' => $pData['personal_card_date_expiry'] ?? null,
                    'passport_number' => $pData['passport_number'] ?? null,
                    'passport_photo' => $pData['passport_photo'] ?? null,
                    'passport_country_place_of_issue' => $pData['passport_country_place_of_issue'] ?? null,
                    'passport_city_place_of_issue' => $pData['passport_city_place_of_issue'] ?? null,
                    'passport_date_issu' => $pData['passport_date_issu'] ?? null,
                    'passport_date_expiry' => $pData['passport_date_expiry'] ?? null,
                ]);

                $this->handlePassengerFileUploads($passengerRecord, $pData);

                // فحص المقعد: إذا حدده العميل نقفله، وإذا لم يحدده نختار تلقائياً
                $requestedSeatId = ! empty($pData['seat_id']) ? (int) $pData['seat_id'] : null;

                $allocatedSeatId = $this->allocateSeatAtomically(
                    $lockedTrip->id,
                    $tripVehicleId,
                    $currentlyBookedSeatIds,
                    $requestedSeatId,
                    $dto->userId
                );

                $currentlyBookedSeatIds[] = $allocatedSeatId;

                // قفل المقعد HELD لمدة دقيقتين
                TripSeat::updateOrCreate(
                    ['trip_id' => $lockedTrip->id, 'seat_id' => $allocatedSeatId],
                    [
                        'trip_seat_status_id' => TripSeatStatus::HELD,
                        'booking_id' => $bookingRecord->id,
                        'user_id' => $dto->userId,
                        'reserved_until' => $reservedUntil,
                    ]
                );

                // إنشاء التذكرة
                $ticket = $this->ticketRepository->create([
                    'trip_reservation_id' => $tripReservationRecord->id,
                    'passenger_id' => $passengerRecord->id,
                    'seat_id' => $allocatedSeatId,
                    'ticket_number' => Str::random(10),
                    'price_at_booking' => $individualTicketPrice,
                    'status' => TicketStatus::ACTIVE,
                ]);

                $seatObj = Seat::find($allocatedSeatId);
                $heldSeatsSummary[] = [
                    'seat_id' => $allocatedSeatId,
                    'seat_name' => $seatObj?->seat_name ?? "المقعد $allocatedSeatId",
                    'status' => 'held',
                    'reserved_until' => $reservedUntil->toIso8601String(),
                ];

                $passengersSummary[] = [
                    'passenger_id' => $passengerRecord->id,
                    'name' => $passengerRecord->full_name,
                    'seat_id' => $allocatedSeatId,
                    'seat_name' => $seatObj?->seat_name ?? "المقعد $allocatedSeatId",
                    'ticket_number' => $ticket->ticket_number,
                ];
            }

            DB::commit();

            return [
                'success' => true,
                'message' => "تمت اضافه المسافرين بنجاح ",
                'data' => [
                    'booking_uuid' => $bookingRecord->uuid,
                    'passengers' => $passengersSummary,
                ],
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('فشل في مرحلة بدء الحجز (Hold): '.$e->getMessage(), [
                'trip_uuid' => $dto->tripUuid,
                'user_id' => $dto->userId,
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error($e->getMessage());
        }
    }

    /**
     * المرحلة الثانية: رابط الدفع وعرض ملخص الـ Hold وطرق الدفع
     * لا يعيد الحساب من الصفر، بل يأخذ المبالغ والضرائب والخصم مباشرة من بيانات الحجز المحفوظة في initiate.
     */
  public function getBookingCheckoutDetails(string $uuid, int $userId): array
{
    $booking = Booking::where('uuid', $uuid)
        ->where('user_id', $userId)
        ->with([
            'currency',
            'taxes.tax',
        ])
        ->first();

    if (!$booking) {
        return $this->error('الحجز غير موجود');
    }

    /*
    |--------------------------------------------------------------------------
    | التحقق من صلاحية Hold
    |--------------------------------------------------------------------------
    */

    $heldTripSeat = TripSeat::where('booking_id', $booking->id)
        ->where('trip_seat_status_id', TripSeatStatus::HELD)
        ->orderByDesc('reserved_until')
        ->first();

    $reservedUntil = $heldTripSeat?->reserved_until;

    $isExpired = false;
    $remainingSeconds = 0;

    if ((int) $booking->booking_status_id === BookingStatus::CONFIRMED) {
        $isExpired = false;
        $remainingSeconds = 0;
    } elseif ($reservedUntil) {
        if ($reservedUntil->isPast()) {
            $isExpired = true;
            $remainingSeconds = 0;
        } else {
            $remainingSeconds = max(
                0,
                now()->diffInSeconds($reservedUntil, false)
            );
        }
    } else {
        $isExpired =
            (int) $booking->booking_status_id === BookingStatus::PENDING;
    }

    /*
    |--------------------------------------------------------------------------
    | طرق الدفع
    |--------------------------------------------------------------------------
    */

    $paymentMethods = Cache::remember(
        'api:payment_methods',
        300,
        function () {
            return \App\Models\PaymentMethod::select(
                'id',
                'method_name'
            )->get();
        }
    );

    /*
    |--------------------------------------------------------------------------
    | الضرائب المحفوظة مع الحجز
    |--------------------------------------------------------------------------
    */

    $taxesSummary = $booking->taxes->map(function ($taxRecord) {
        return [
            'name' => $taxRecord->tax?->name ?? 'ضريبة',
            'amount' => (float) $taxRecord->tax_amount,
        ];
    })->values();

    /*
    |--------------------------------------------------------------------------
    | النتيجة
    |--------------------------------------------------------------------------
    */

    return [
        'success' => true,

        'message' => $isExpired
            ? 'انتهت مهلة الحجز المؤقت (Hold) للمقاعد'
            : 'تم استرجاع تفاصيل الدفع والـ Hold بنجاح',

        'data' => [
            'booking_uuid' => $booking->uuid,

            'booking_status' => $booking->booking_status_id,

            'is_hold_expired' => $isExpired,

            'hold_info' => [
                'reserved_until' => $reservedUntil
                    ? $reservedUntil->toIso8601String()
                    : null,

                'remaining_seconds' => $remainingSeconds,

                'remaining_minutes' => round(
                    $remainingSeconds / 60,
                    1
                ),

                'is_expired' => $isExpired,
            ],

            'financial_summary' => [
                'total_amount' => (float) $booking->amount,

                'base_amount' => (float) $booking->base_amount,

                'currency_id' => $booking->currency_id,

                'currency_name' => $booking->currency?->name,

                'currency_symbol' => $booking->currency?->symbol,

                'exchange_rate' => (float) $booking->exchange_rate,

                'taxes' => $taxesSummary,
            ],

            'payment_methods' => $paymentMethods,
        ],
    ];
}
    /**
     * تأكيد الدفع للحجز الموجود مسبقاً (دون إنشاء Booking جديد)
     * يكمل على نفس الـ Booking ويحول المقاعد من HELD إلى BOOKED والحجز إلى CONFIRMED
     */
    public function confirmBookingPayment(string $uuid, int $userId, int $paymentMethodId, ?string $stripeToken = null): array
    {
        $booking = Booking::where('uuid', $uuid)
            ->where('user_id', $userId)
            ->with(['tripReservations.trip.vehicle', 'tripReservations.tickets', 'taxes'])
            ->first();

        if (! $booking) {
            return $this->error('الحجز غير موجود');
        }

        if ((int) $booking->booking_status_id === BookingStatus::CONFIRMED) {
            return $this->error('الحجز مؤكد ومدفوع بالفعل');
        }

        if ((int) $booking->booking_status_id !== BookingStatus::PENDING) {
            return $this->error('حالة الحجز لا تسمح بالدفع');
        }

        // فحص صلاحية الـ Hold
        $heldTripSeats = TripSeat::where('booking_id', $booking->id)
            ->where('trip_seat_status_id', TripSeatStatus::HELD)
            ->get();

        foreach ($heldTripSeats as $ts) {
            if ($ts->reserved_until && $ts->reserved_until->isPast()) {
                return $this->error('انتهت مهلة الحجز المؤقت (Hold) للمقاعد. يرجى بدء حجز جديد.');
            }
        }

        try {
            DB::beginTransaction();

            $paymentAmount = (float) $booking->amount;
            $paymentCurrencyId = $booking->currency_id;

            // تنفيذ الدفع حسب الوسيلة المختارة
            $gatewayTransactionId = match ($paymentMethodId) {
                PaymentMethod::WALLET => $this->processWalletPayment($userId, $paymentCurrencyId, $paymentAmount, $booking->id),
                PaymentMethod::CREDIT_CARD,
                PaymentMethod::BANK_TRANSFER => $this->processGatewayPayment($paymentMethodId, $paymentAmount, $stripeToken),
                PaymentMethod::CASH => null,
                default => throw new \Exception('طريقة دفع غير مدعومة'),
            };

            // إنشاء سجل الدفع BookingPayment
            $paymentRecord = BookingPayment::create([
                'booking_id' => $booking->id,
                'user_id' => $userId,
                'payment_method_id' => $paymentMethodId,
                'payed_amount' => $paymentAmount,
                'currency_id' => $paymentCurrencyId,
                'exchange_rate' => $booking->exchange_rate,
                'base_amount' => $booking->base_amount,
                'note' => $gatewayTransactionId ? "معرف المعاملة: {$gatewayTransactionId}" : null,
            ]);

            // تحويل حالة المقاعد من HELD إلى BOOKED وإزالة reserved_until
            TripSeat::where('booking_id', $booking->id)->update([
                'trip_seat_status_id' => TripSeatStatus::BOOKED,
                'reserved_until' => null,
            ]);

            // خصم المقاعد من عداد الرحلة لمنع Overbooking
            foreach ($booking->tripReservations as $reservation) {
                $ticketsCount = $reservation->tickets->count();
                $this->tripRepository->decrementSeats($reservation->trip_id, $ticketsCount);
            }

            // القيود المحاسبية
            $transactionReference = TransactionReference::create([
                'reference_number' => 'BOOK-'.strtoupper(Str::random(8)),
                'reference_type' => 'booking',
                'reference_id' => $booking->id,
                'created_by' => $userId,
                'status' => TransactionReferenceStatus::DRAFT,
                'description' => 'حجز رحلة - '.$booking->uuid,
            ]);

            $this->recordAccountingEntries(
                $booking,
                $paymentAmount,
                $userId,
                $transactionReference,
                [
                    'commission_amount' => $booking->commission_amount,
                ]
            );

            // تحديث حالة الحجز إلى CONFIRMED
            $booking->update([
                'booking_status_id' => BookingStatus::CONFIRMED,
            ]);

            DB::commit();

            // الإشعار والتدقيق
            $this->auditService->log($userId, 'payment_confirmed', 'bookings', $booking->id);
            $this->notificationService->notifyBookingSafe($booking, $paymentAmount, NotificationTypes::BOOKING_CONFIRMED);

            return [
                'success' => true,
                'message' => 'تم تأكيد الدفع وتثبيت الحجز بنجاح',
                'data' => [
                    'booking_uuid' => $booking->uuid,
                    'booking' => (new BookingResource($booking->fresh()))->toArray(request()),
                ],
            ];
        } catch (InsufficientBalanceException $e) {
            DB::rollBack();
            return $this->error($e->getMessage());
        } catch (WalletDisabledException $e) {
            DB::rollBack();
            return $this->error($e->getMessage());
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('فشل تأكيد دفع الحجز: '.$e->getMessage(), [
                'booking_uuid' => $uuid,
                'user_id' => $userId,
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error('فشل في إتمام عملية الدفع: '.$e->getMessage());
        }
    }

    /**
     * Process payment failure with audit logging
     */
    public function handlePaymentFailure(string $bookingUuid, string $failureReason, array $paymentDetails): array
    {
        try {
            DB::beginTransaction();

            $booking = Booking::where('uuid', $bookingUuid)->firstOrFail();

            // Update booking status to payment failed
            $booking->update([
                'booking_status_id' => BookingStatus::PAYMENT_FAILED,
                'payment_failure_reason' => $failureReason,
                'payment_failed_at' => now(),
            ]);

            // Log payment failure in transactions
            Transaction::create([
                'uuid' => Str::uuid(),
                'user_id' => $booking->user_id,
                'booking_id' => $booking->id,
                'transaction_type' => 'payment_failure',
                'amount' => $booking->amount,
                'description' => "Payment failed: {$failureReason}",
                'status' => 'failed',
                'failure_reason' => $failureReason,
                'payment_details' => json_encode($paymentDetails),
            ]);

            // Release reserved seats
            TripReservation::where('booking_id', $booking->id)
                ->update(['status' => 'released']);

            // Log payment failure for audit
            $this->auditService->log(
                $booking->user_id,
                'PAYMENT_FAILURE',
                'bookings',
                $booking->id,
                null,
                [
                    'booking_uuid' => $bookingUuid,
                    'failure_reason' => $failureReason,
                    'amount' => $booking->amount,
                    'payment_details' => $paymentDetails,
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]
            );

            DB::commit();

            return [
                'success' => true,
                'message' => 'Payment failure processed successfully',
                'booking_status' => 'payment_failed',
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Payment failure handling failed', [
                'booking_uuid' => $bookingUuid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'فشل في معالجة الدفع',
            ];
        }
    }

    /**
     * Process multi-trip booking with comprehensive security and validation
     *
     * This method implements a 5-phase booking process to ensure data integrity
     * and prevent race conditions under high load scenarios.
     *
     * Phases:
     * 1. Validation - Basic input validation and permission checks
     * 2. Preparation - Fetch trips and JIT materialization outside transaction
     * 3. Calculation - Compute final amount once with taxes and discounts
     * 4. Lock & Execute - Atomic database operations with ordered locking
     * 5. Persistence - Save all records and financial transactions
     *
     * @param  BookTripDTO  $dto  Booking data transfer object
     * @return array Result array with success status and booking data
     *
     * @throws \Exception When database operations fail or validation errors occur
     */
    public function bookTrips(BookTripDTO $dto): array
    {

        // Phase 1: Basic Input Validation
        // Why: Early validation prevents unnecessary database operations
        $totalPassengersCount = $dto->adults + $dto->children;
        if ($totalPassengersCount <= 0) {
            return $this->error('يجب تحديد عدد المسافرين');
        }

        if (! empty($dto->passengersData) && count($dto->passengersData) !== $totalPassengersCount) {
            return $this->error('عدد المسافرين المرسل لا يتطابق مع إجمالي البالغين والأطفال');
        }

        // Phase 2: Trip Preparation (Outside Transaction)
        // Why: Expensive operations like trip generation should happen outside DB transaction
        // to reduce lock time and improve performance
        $validatedTrips = [];
        foreach ($dto->tripUuids as $tripIndex => $tripUuid) {
            $trip = $this->tripRepository->findByUuid($tripUuid);
            if (! $trip) {
                return $this->error("الرحلة {$tripUuid} غير موجودة");
            }

            // JIT (Just-In-Time) Trip Materialization
            // Why: Generate child trips only when needed to save resources
            $requestedDepartureDate = $dto->departureDates[$tripIndex] ?? null;
            if ($requestedDepartureDate) {
                $targetDate = Carbon::parse($requestedDepartureDate)->startOfDay();
                $parentTripDepartureDate = Carbon::parse($trip->departure_time)->startOfDay();

                if ($targetDate->gt($parentTripDepartureDate)) {
                    $existingChildTrip = Trip::where('parent_id', $trip->id)
                        ->whereDate('departure_time', $targetDate)
                        ->first();

                    if ($existingChildTrip) {
                        $trip = $existingChildTrip;
                    } else {
                        // Generate new child trip dynamically
                        $trip = $this->spawnTripFromParent($trip, $targetDate);
                        if (! $trip) {
                            return $this->error("هذه الرحلة غير متاحة في التاريخ المطلوب ({$targetDate->toDateString()})");
                        }
                    }
                }
            }

            // Permission Validation
            // Why: Ensure user can only book trips they're authorized to access
            if (! $trip->is_public && $trip->user_id !== $dto->userId && ! auth()->user()?->hasPermission('trips:book-any')) {
                return $this->error("الرحلة {$trip->uuid} غير متاحة للحجز");
            }

            // Business Logic: Time-based Restrictions
            // Why: Prevent booking trips that have already started or expired
            if ($trip->departure_time <= now()) {
                return $this->error("الرحلة {$trip->uuid} قد بدأت بالفعل، لا يمكن حجزها");
            }

            // Business Logic: Booking Deadline (2 hours before departure)
            // Why: Give operators time to prepare and prevent last-minute issues
            $bookingDeadline = $trip->departure_time->subHours(2);
            if (now() > $bookingDeadline) {
                return $this->error("انتهى وقت حجز الرحلة {$trip->uuid}");
            }

            // Prevent duplicate trip selection in same booking
            if (in_array($trip->id, array_column($validatedTrips, 'id'))) {
                return $this->error('تم اختيار نفس الرحلة أكثر من مرة');
            }

            $validatedTrips[] = $trip;
        }

        // Phase 3: Financial Calculation (Once, Outside Transaction)
        // Why: Calculate once to avoid redundant computations and ensure consistency
        $baseBookingAmount = 0;
        foreach ($validatedTrips as $trip) {
            $baseBookingAmount += ($dto->adults * $trip->price_for_adult) + ($dto->children * $trip->price_for_child);
        }

        // Coupon Processing
        // Why: Apply discounts before taxes for accurate financial calculation
        $appliedCoupon = null;
        $discountAmount = 0;

        if ($dto->couponCode) {
            $appliedCoupon = $this->couponService->findValidCoupon($dto->couponCode);
            if (! $appliedCoupon) {
                return $this->error('الكوبون غير صالح أو منتهي الصلاحية');
            }

            $applicableTrips = $this->couponService->getCouponApplicableTrips($appliedCoupon, $validatedTrips);
            if (empty($applicableTrips)) {
                return $this->error('هذا الكوبون لا ينطبق على الرحلات المختارة');
            }

            $applicableAmount = 0;
            foreach ($applicableTrips as $applicableTrip) {
                $applicableAmount += ($dto->adults * $applicableTrip->price_for_adult) + ($dto->children * $applicableTrip->price_for_child);
            }

            $discountedAmount = $this->couponService->applyDiscount($applicableAmount, $appliedCoupon);
            $discountAmount = $applicableAmount - $discountedAmount;
        }

        if ($dto->outboundCouponCode) {
            $outboundCoupon = $this->couponService->findValidCoupon($dto->outboundCouponCode);
            if (! $outboundCoupon) {
                return $this->error('كوبون الذهاب غير صالح أو منتهي الصلاحية');
            }

            $outboundTrip = [$validatedTrips[0]];
            $applicableOutbound = $this->couponService->getCouponApplicableTrips($outboundCoupon, $outboundTrip);
            if (empty($applicableOutbound)) {
                return $this->error('كوبون الذهاب لا ينطبق على رحلة الذهاب');
            }

            $outboundAmount = ($dto->adults * $validatedTrips[0]->price_for_adult) + ($dto->children * $validatedTrips[0]->price_for_child);
            $outboundDiscounted = $this->couponService->applyDiscount($outboundAmount, $outboundCoupon);
            $discountAmount += ($outboundAmount - $outboundDiscounted);

            if (! $appliedCoupon) {
                $appliedCoupon = $outboundCoupon;
            }
        }

        if ($dto->returnCouponCode && count($validatedTrips) > 1) {
            $returnCoupon = $this->couponService->findValidCoupon($dto->returnCouponCode);
            if (! $returnCoupon) {
                return $this->error('كوبون العودة غير صالح أو منتهي الصلاحية');
            }

            $returnTrip = [$validatedTrips[1]];
            $applicableReturn = $this->couponService->getCouponApplicableTrips($returnCoupon, $returnTrip);
            if (empty($applicableReturn)) {
                return $this->error('كوبون العودة لا ينطبق على رحلة العودة');
            }

            $returnAmount = ($dto->adults * $validatedTrips[1]->price_for_adult) + ($dto->children * $validatedTrips[1]->price_for_child);
            $returnDiscounted = $this->couponService->applyDiscount($returnAmount, $returnCoupon);
            $discountAmount += ($returnAmount - $returnDiscounted);

            if (! $appliedCoupon) {
                $appliedCoupon = $returnCoupon;
            }
        }

        // Final Amount Calculation with Taxes
        $financialCalculation = $this->bookingCalculationService->calculateBookingCosts($baseBookingAmount, $appliedCoupon, $validatedTrips, $discountAmount);
        $finalBookingAmount = $financialCalculation['total_amount'];

        // Data Integrity Validation
        // Why: Ensure all business rules are satisfied before database operations
        $dataIntegrityErrors = $this->dataIntegrityService->validateBookingData([
            'adults' => $dto->adults,
            'children' => $dto->children,
            'amount' => $baseBookingAmount,
        ]);

        if (! empty($dataIntegrityErrors)) {
            return $this->error(__('messages.validation_failed').': '.implode(', ', $dataIntegrityErrors));
        }
        $idempotencyKey = request()->header('Idempotency-Key')
            ?? 'booking_'.$dto->userId.'_'
             .md5(
                 implode('_', $dto->tripUuids).'_'
                  .$dto->adults.'_'
                   .$dto->children.'_'
                    .($dto->couponCode ?? 'nocoupon'));
        if (! Cache::add($idempotencyKey, true, 120)) {
            return $this->error('طلب مكرر - تمت معالجة هذا الطلب مسبقاً');
        }
        // Phase 4: Atomic Database Operations with Security Locks
        // Why: Use database transaction to ensure all operations succeed or fail together
        try {
            DB::beginTransaction();

            // Ordered Locking to Prevent Deadlocks
            // Why: Lock trips in ascending ID order to prevent circular waiting
            $sortedTripsForLocking = collect($validatedTrips)->sortBy('id');
            $lockedTripsCollection = [];
            $bookedSeatIdsByTrip = []; // Store booked seat IDs for each trip

            foreach ($sortedTripsForLocking as $trip) {
                $lockedTrip = $this->tripRepository->lockForUpdate($trip->id);
                if (! $lockedTrip) {
                    throw new \Exception("فشل قفل الرحلة {$trip->uuid}");
                }

                // Final Seat Availability Check
                // Why: Double-check seats are still available after acquiring lock
                /*if ($lockedTrip->available_seats < $totalPassengersCount) {
                    throw new \Exception("المقاعد نفدت في الرحلة {$trip->uuid}");
                }*/
                $availableSeats = $lockedTrip->available_seats
                    ?? ($lockedTrip->vehicle->seats()->count() - count($lockedTrip->getBookedSeatIds()));

                if ($availableSeats < $totalPassengersCount) {
                    throw new \Exception("المقاعد نفدت في الرحلة {$trip->uuid}");
                }

                // Validate specific seat requests against current bookings and holds
                // Why: Prevent stale data - seats shown as available may have been booked or held by others
                $seatsForValidation = $this->prepareSeatsForValidation($dto->passengersData, $dto->tripUuids, $trip->uuid);
                $seatValidation = $this->validateSeatAvailability($lockedTrip, $seatsForValidation, true, $dto->userId);

                if (! $seatValidation['valid']) {
                    throw new \InvalidArgumentException($seatValidation['message']);
                }

                // Store booked seat IDs to pass to ticket creation
                // Why: Ensure consistency - don't re-fetch seats after validation
                $validatedBookedSeatIds = $seatValidation['booked_seat_ids'] ?? [];
                $bookedSeatIdsByTrip[$trip->id] = $validatedBookedSeatIds;

                // Final Time Validation (Double Check)
                // Why: Ensure trip hasn't started while waiting for locks
                if ($lockedTrip->departure_time <= now()) {
                    throw new \Exception("الرحلة {$trip->uuid} قد بدأت بالفعل، لا يمكن حجزها");
                }

                // Final Booking Deadline Check (Double Check)
                // Why: Ensure booking window hasn't expired while waiting for locks
                $bookingDeadline = $lockedTrip->departure_time->subHours(2);
                if (now() > $bookingDeadline) {
                    throw new \Exception("انتهى وقت حجز الرحلة {$trip->uuid}");
                }

                $lockedTripsCollection[] = $lockedTrip;
            }

            // Phase 5: Data Persistence with Financial Transactions
            // Note: $bookedSeatIdsByTrip contains the consistent seat status from validation
            // Why: Save all data in a consistent state with proper financial records

            // Calculate Branch and Commission
            $bookingBranchId = null;
            $bookingCommission = ['id' => null, 'value' => 0, 'amount' => 0];

            if (! empty($validatedTrips)) {
                $branch = $this->branchService->getBranchByTrip($validatedTrips[0]);
                $bookingBranchId = $branch?->id;
                $bookingCommission = $this->commissionService->calculateBookingCommission($financialCalculation['taxable_amount'], $bookingBranchId);
            }
            $baseCurrencyId = $this->currencyService->baseCurrencyId();
            $paymentCurrencyId = $dto->paymentCurrencyId ?? $baseCurrencyId;
            $exchangeRate = $this->exchangeRateService->getRate($paymentCurrencyId, $baseCurrencyId);
            if (! $exchangeRate || $exchangeRate <= 0) {
                return $this->error('سعر الصرف غير متاح للعملة المختارة - يرجى اختيار عملة أخرى');
            }
            $baseAmount = $finalBookingAmount;
            $paymentAmount = round($finalBookingAmount * $exchangeRate, 2);

            // === Payment Processing Integration (Using Match with Constants) ===
            $paymentMethodId = $dto->paymentMethodId;

            $gatewayTransactionId = match ($paymentMethodId) {
                // 1. الدفع عبر المحفظة
                PaymentMethod::WALLET => $this->processWalletPayment($dto->userId, $paymentCurrencyId, $paymentAmount),
                PaymentMethod::CREDIT_CARD,
                PaymentMethod::BANK_TRANSFER => $this->processGatewayPayment($paymentMethodId, $paymentAmount, $dto->stripeToken),
                PaymentMethod::CASH => null,

                default => throw new \Exception('طريقة دفع غير مدعومة'),
            };

            // Create Payment Record
            $paymentRecord = BookingPayment::create([
                'user_id' => $dto->userId,
                'payment_method_id' => $dto->paymentMethodId,
                'payed_amount' => $paymentAmount,
                'currency_id' => $paymentCurrencyId,
                'exchange_rate' => $exchangeRate,
                'base_amount' => $baseAmount,
                'note' => null,
            ]);

            // Create Main Booking Record
            $bookingRecord = $this->bookingRepository->create([
                'uuid' => Str::uuid()->toString(),
                'user_id' => $dto->userId,
                'branch_id' => $bookingBranchId,
                'booking_status_id' => BookingStatus::PENDING,
                'amount' => $paymentAmount,
                'currency_id' => $paymentCurrencyId,
                'exchange_rate' => $exchangeRate,
                'base_amount' => $baseAmount,
                'commission_id' => $bookingCommission['id'],
                'commission_value' => $bookingCommission['value'],
                'commission_amount' => $bookingCommission['amount'],
                'commission_type_fixed_or_percentage' => $bookingCommission['type'] ?? 'percentage',
                'note' => null,
            ]);

            // Link Payment to Booking
            $paymentRecord->update(['booking_id' => $bookingRecord->id]);

            // Create Tax Records
            $this->bookingCalculationService->createBookingTaxes($bookingRecord->id, $financialCalculation['tax_details'], $paymentCurrencyId, $exchangeRate);

            // Create Trip Reservations and Tickets
            foreach ($validatedTrips as $tripIndex => $trip) {
                $this->createTripReservation(
                    $trip,
                    $bookingRecord,
                    $dto->passengersData,
                    $totalPassengersCount,
                    $tripIndex,
                    $validatedTrips,
                    $finalBookingAmount,
                    $bookedSeatIdsByTrip[$trip->id] ?? []  // Pass consistent seat status from validation
                );
            }

            // Process Coupon Usage
            if ($appliedCoupon) {
                $this->couponService->recordCouponUsage($appliedCoupon, $dto->userId, $bookingRecord->id, $discountAmount);
                $this->couponService->markAsUsed($appliedCoupon);
            }

            // Create Financial Accounting Records
            // Create Financial Accounting Records
            $transactionReference = TransactionReference::create([
                'reference_number' => 'BOOK-'.strtoupper(Str::random(8)),
                'reference_type' => 'booking',
                'reference_id' => $bookingRecord->id,
                'created_by' => $dto->userId,
                'status' => TransactionReferenceStatus::DRAFT,
                'description' => 'حجز رحلة - '.$bookingRecord->uuid,
            ]);

            // Validate Final Amount Consistency
            $expectedFinalAmount = $financialCalculation['total_amount'] ?? $finalBookingAmount;
            if (abs($finalBookingAmount - $expectedFinalAmount) > 0.01) {
                throw new \Exception("عدم تطابق في المبلغ النهائي: {$finalBookingAmount} vs {$expectedFinalAmount}");
            }

            // Record accounting entries based on configured mode (legacy/receivables/parallel)
            $this->recordAccountingEntries(
                $bookingRecord,
                $paymentAmount,
                $dto->userId,
                $transactionReference,
                array_merge($financialCalculation, [
                    'trip_ids' => collect($validatedTrips)->pluck('id')->toArray(),
                    'commission_amount' => $bookingCommission['amount'],
                ])
            );

            $this->bookingRepository->update($bookingRecord, [
                'booking_status_id' => BookingStatus::CONFIRMED,
            ]);

            DB::commit();
            // Confirm Balance Deduction After Successful Booking
            // Why: Only confirm deduction after all operations succeed

            // Audit Logging
            $this->auditService->log($dto->userId, 'created', 'bookings', $bookingRecord->id);
            $this->notificationService->notifyBookingSafe($bookingRecord, $finalBookingAmount, NotificationTypes::BOOKING_CONFIRMED);

            /*$paymentMethod = PaymentMethodModel::find($dto->paymentMethodId);
            return $this->success('تم الحجز بنجاح', [
                'booking_uuid' => $bookingRecord->uuid,
                'amount' => number_format($finalBookingAmount, 2, '.', ''),
                'payment_method_name' => $paymentMethod?->method_name ?? 'Unknown',
            ]);*/
            return [
                'success' => true,
                'message' => 'تم حجز الرحلة بنجاح',
                'data' => [
                    'booking_uuid' => $bookingRecord->uuid,
                    'booking' => (new BookingResource($bookingRecord))->toArray(request()),
                ],
            ];
        } catch (InsufficientBalanceException $e) {
            DB::rollBack();
            Cache::forget($idempotencyKey);

            return $this->error($e->getMessage());
        } catch (WalletDisabledException $e) {
            DB::rollBack();
            Cache::forget($idempotencyKey);

            return $this->error($e->getMessage());
        } catch (\Exception $e) {
            DB::rollBack();
            Cache::forget($idempotencyKey);
            Log::error('BOOKING ERROR: '.$e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            // Specific Error Messages for Better UX
            if (str_contains($e->getMessage(), 'المقاعد نفدت')) {
                return $this->error('عذراً، لم تعد هناك مقاعد متاحة في هذه الرحلة. يرجى اختيار رحلة أخرى أو وقت مختلف.');

            } elseif (str_contains($e->getMessage(), 'فشل قفل الرحلة')) {
                return $this->error('النظام يشهد ضغطاً حالياً، يرجى المحاولة مرة أخرى بعد قليل.');

            } elseif (str_contains($e->getMessage(), 'انتهى وقت حجز الرحلة')) {
                return $this->error('عذراً، وقت الحجز لهذه الرحلة قد انتهى. يرجى اختيار رحلة أخرى.');

            } elseif (str_contains($e->getMessage(), 'الرحلة قد بدأت بالفعل')) {
                return $this->error('لا يمكن حجز رحلة بدأت بالفعل. يرجى اختيار رحلة قادمة.');
            } elseif ($e instanceof QueryException) {
                return $this->error('حدث خطأ في النظام. يرجى المحاولة لاحقاً أو التواصل مع خدمة العملاء.');

            } else {
                return $this->error('عذراً، لم نتمكن من إتمام الحجز. يرجى التحقق من البيانات والمحاولة مرة أخرى.');
            }
        }
    }

    /**
     * جلب حجوزات المستخدم مع تحسين الأداء
     */
    public function getUserBookings(int $userId, int $perPage = 15)
    {
        return $this->bookingRepository->getUserBookings($userId, $perPage);
    }

    /**
     * جلب جميع الحجوزات للإداريين
     */
    public function getAllBookings(int $perPage = 15, ?string $statusFilter = null, ?string $search = null)
    {
        return $this->bookingRepository->getAllBookings($perPage, $statusFilter, $search);
    }

    /**
     * جلب تفاصيل حجز معين مع تحسين الأداء
     */
    public function getBookingDetails(string $uuid, int $userId)
    {
        $relations = [
            'status:id,status_name',
            'payment:id,booking_id,payment_method_id,payed_amount',
            'payment.paymentMethod:id,method_name',
            'user:id,name,email',
            'tripReservations.trip',
            'tripReservations.trip.departureCity:id,name',
            'tripReservations.trip.destinationCity:id,name',
            'tripReservations.trip.vehicle',
            'tripReservations.trip.vehicle.vehicleType:id,type_name',
            'tripReservations.tickets.passenger:id,full_name,birthday',
            'tripReservations.tickets.seat:id,seat_name',
            'taxes.tax:id,name,type,rate',
        ];

        $booking = $this->bookingRepository->findByUuid($uuid, $relations);

        if (! $booking) {
            return null;
        }
        if ($booking->user_id !== $userId && ! auth()->user()?->hasPermission('bookings:view-any')) {
            return null;
        }

        return $booking;
    }

    protected function generatePassengersList(int $adults, int $children): array
    {
        $passengers = [];
        for ($i = 1; $i <= $adults; $i++) {
            $passengers[] = ['name' => "بالغ $i", 'age' => 30];
        }
        for ($i = 1; $i <= $children; $i++) {
            $passengers[] = ['name' => "طفل $i", 'age' => 8];
        }

        return $passengers;
    }

    /**
     * إلغاء حجز (Cancellation) مع تحسين الأمان والأداء
     */
    public function cancelBooking(Booking $booking, int $userId): array
    {
        $booking->loadMissing([
            'tripReservations.trip:id,departure_time,vehicle_id',
            'tripReservations.trip.vehicle:id,user_id',
            'tripReservations.tickets:id,cancellation_fee,refund_amount',
        ]);

        // التحقق من الملكية (أو صلاحية الإدارة)
        $isOwner = $booking->user_id === $userId;
        $isAdmin = auth()->user()?->hasPermission('bookings:cancel-any');

        if (! $isOwner && ! $isAdmin) {
            return $this->error('غير مصرح لك بإلغاء هذا الحجز');
        }

        if ($booking->booking_status_id === BookingStatus::CANCELLED ||
            $booking->booking_status_id === BookingStatus::CANCELLED_BY_USER ||
            $booking->booking_status_id === BookingStatus::CANCELLED_BY_ADMIN) {
            return $this->error('الحجز ملغي بالفعل');
        }

        // التحقق من أن الرحلات لم تبدأ بعد
        foreach ($booking->tripReservations as $reservation) {
            if ($reservation->trip->departure_time <= now()) {
                return $this->error('لا يمكن إلغاء الحجز لأن إحدى الرحلات قد بدأت أو انتهت بالفعل');
            }
        }

        try {
            DB::beginTransaction();

            // 1. استخدام RefundService الموحد لمعالجة الإلغاء
            $result = $this->refundService->cancelBooking($booking, auth()->user());

            if (! $result['success']) {
                throw new \Exception($result['message'] ?? 'فشل إلغاء الحجز');
            }

            $refundAmount = $result['refund_amount'] ?? 0;
            $cancellationFee = $result['cancellation_fee'] ?? 0;

            // تحديث المقاعد المتاحة بعد الإلغاء
            foreach ($booking->tripReservations as $reservation) {
                $trip = $reservation->trip;
                $ticketCount = $reservation->tickets()->count();
                $trip->increment('available_seats', $ticketCount);
                $trip->clearSeatsCache();
            }

            DB::commit();

            // ✅ Audit + Notification في afterCommit
            DB::afterCommit(function () use ($userId, $booking, $refundAmount, $cancellationFee) {
                $this->auditService->log($userId, 'cancelled', 'bookings', $booking->id);
                $this->notificationService->notifyBookingSafe(
                    $booking,
                    $refundAmount,
                    NotificationTypes::BOOKING_CANCELLED,
                    $cancellationFee
                );
            });

            return $this->success('تم إلغاء الحجز بنجاح', [
                'refund_amount' => $refundAmount,
                'cancellation_fee' => $cancellationFee,
                'booking_uuid' => $booking->uuid,
                'user_id' => $userId,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('فشل إلغاء الحجز: '.$e->getMessage());

            return $this->error('فشل في إلغاء الحجز: ');
        }
    }

    /**
     * تحرير جميع أقفال الرحلات
     */
    private function releaseAllLocks(array $trips): void
    {
        foreach ($trips as $trip) {
            try {
                // تحرير القفل من خلال Cache أو أي آلية أخرى
                $lockKey = "trip_lock_{$trip->id}";
                Cache::forget($lockKey);
                Cache::forget("{$lockKey}_time");
            } catch (\Exception $e) {
                Log::warning('Failed to release trip lock', [
                    'trip_id' => $trip->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    protected function success(string $message, array $data = []): array
    {
        return ['success' => true, 'message' => $message, 'data' => $data];
    }

    protected function error(string $message): array
    {
        return ['success' => false, 'message' => $message];
    }

    /**
     * جلب مقاعد الرحلة مع حالتها
     */
    public function getTripSeats(string $tripUuid): array
    {
        $trip = $this->tripRepository->findByUuid($tripUuid);
        if (! $trip) {
            return [];
        }

        return $trip->getSeatsWithStatus()->toArray();
    }

    /**
     * حساب رسوم الإلغاء بناءً على قواعد الإلغاء
     */
    protected function calculateCancellationFee(Booking $booking): float
    {
        // جلب سياسة الإلغاء
        $policy = Policy::where('type', 'cancellation')->first();
        if (! $policy) {
            return 0;
        }

        // جلب أقرب رحلة للتحقق من الوقت المتبقي
        $closestTrip = $booking->tripReservations()
            ->with('trip')
            ->get()
            ->sortBy(function ($reservation) {
                return $reservation->trip->departure_time;
            })
            ->first();

        if (! $closestTrip) {
            return 0;
        }

        $hoursBeforeStart = $closestTrip->trip->departure_time->diffInHours(now());

        // جلب قاعدة الإلغاء المناسبة
        $rule = PolicyCancellationRule::where('policy_id', $policy->id)
            ->where('from_hours', '<=', $hoursBeforeStart)
            ->where('to_hours', '>=', $hoursBeforeStart)
            ->first();

        if (! $rule) {
            return 0;
        }

        return $booking->amount * ($rule->fee_percentage / 100);
    }

    /**
     * Create trip reservation with tickets and seat allocation
     *
     * This method creates a trip reservation and associated tickets while
     * properly allocating seats and updating trip capacity.
     *
     * Security considerations:
     * - Uses pre-calculated final amount to ensure pricing consistency
     * - Decrements seats atomically to prevent overbooking
     * - Maintains audit trail for all operations
     *
     * @param  Trip  $trip  The trip instance
     * @param  Booking  $booking  The main booking record
     * @param  array  $passengersData  Array of passenger information
     * @param  int  $totalSeatsCount  Total number of seats to allocate
     * @param  int  $tripIndex  Index of this trip in multi-trip booking
     * @param  array  $allTrips  All trips in this booking
     * @param  float  $finalBookingAmount  Final calculated amount for entire booking
     * @return array Success status and reservation data
     *
     * @throws \Exception When seat allocation fails
     */
    protected function createTripReservation(
        Trip $trip,
        Booking $booking,
        array $passengersData,
        int $totalSeatsCount,
        int $tripIndex,
        array $allTrips,
        float $finalBookingAmount,
        array $bookedSeatIds = []  // Pre-fetched booked seat IDs from validation
    ): array {
        // Calculate trip-specific amount based on actual trip prices
        // Why: Each trip has its own price (price_for_adult, price_for_child)
        // Round-trip bookings may have different prices for outbound vs return trips
        $tripSpecificAmount = $this->calculateTripPrice($trip, $passengersData);

        // Determine trip direction for round-trip bookings
        // Why: Provide clear distinction between outbound and return trips
        $tripDirection = count($allTrips) > 1 ? ($tripIndex === 0 ? 'outbound' : 'return') : null;

        // Create trip reservation record
        $tripReservationRecord = TripReservation::create([
            'booking_id' => $booking->id,
            'trip_id' => $trip->id,
            'direction' => $tripDirection,
            'price_at_booking' => $tripSpecificAmount, // Use pre-calculated amount
            'status' => 'active',
        ]);

        // Create tickets with atomic seat allocation
        // Why: Ensure each passenger gets a valid seat without conflicts
        // Pass pre-fetched bookedSeatIds to ensure consistency with validation
        // $this->createTickets($tripReservationRecord, $trip, $passengersData, $tripSpecificAmount, $bookedSeatIds);
        //  تجهيز بيانات المسافرين بمقاعد الرحلة الصحيحة
        $passengersWithCorrectSeats = [];
        foreach ($passengersData as $index => $passenger) {
            $passengerCopy = $passenger;

            if ($tripDirection === 'outbound') {
                $passengerCopy['seat_id'] = $passenger['outbound_seat_id'] ?? null;
            } elseif ($tripDirection === 'return') {
                $passengerCopy['seat_id'] = $passenger['return_seat_id'] ?? null;
            }
            unset($passengerCopy['outbound_seat_id'], $passengerCopy['return_seat_id']);

            $passengersWithCorrectSeats[] = $passengerCopy;
        }

        $this->createTickets($tripReservationRecord, $trip, $passengersWithCorrectSeats, $tripSpecificAmount, $bookedSeatIds);
        // Decrement available seats atomically
        // Why: Prevent overbooking by updating seat count in the same transaction
        $seatDecrementSuccess = $this->tripRepository->decrementSeats($trip->id, $totalSeatsCount);
        if (! $seatDecrementSuccess) {
            throw new \Exception("فشل تحديث عدد المقاعد المتاحة للرحلة {$trip->id}");
        }

        return ['success' => true, 'reservation' => $tripReservationRecord];
    }

    /**
     * التحقق من توفر المقاعد مع منع Double Booking وفحص الحجز المؤقت (Hold)
     *
     * @param  Trip  $trip  The trip (already locked if called inside transaction)
     * @param  array  $passengersData  Array of passenger data with seat_id
     * @param  bool  $alreadyLocked  Whether the trip is already locked (when called inside transaction)
     * @param  int|null  $userId  ID العميل للتحقق من ملكية الـ Hold
     * @return array Validation result with 'valid' boolean and 'message' string
     */
    protected function validateSeatAvailability(Trip $trip, array $passengersData, bool $alreadyLocked = false, ?int $userId = null): array
    {
        // Use already locked trip if provided, otherwise lock for update
        // Why: When called inside a transaction, trip is already locked
        $tripToCheck = $alreadyLocked ? $trip : $this->tripRepository->lockForUpdate($trip->id);

        // Get fresh booked seat IDs from database
        // Why: Ensure we have the latest data even inside a transaction
        $bookedSeatIds = $tripToCheck->getBookedSeatIds();
        $requestedSeatIds = array_filter(array_column($passengersData, 'seat_id'));

        if (count($requestedSeatIds) > 0) {
            // التحقق من عدم التكرار
            if (count($requestedSeatIds) !== count(array_unique($requestedSeatIds))) {
                return ['valid' => false, 'message' => 'لا يمكن اختيار نفس المقعد لأكثر من مسافر في نفس الطلب'];
            }

            // التحقق من أن المقاعد تنتمي للمركبة
            $vehicleSeatIds = $tripToCheck->vehicle->seats()->pluck('id')->toArray();
            foreach ($requestedSeatIds as $seatId) {
                if (! in_array($seatId, $vehicleSeatIds)) {
                    return ['valid' => false, 'message' => "المقعد رقم {$seatId} لا ينتمي لحافلة هذه الرحلة"];
                }
            }

            // التحقق من عدم الحجز المسبق للتذاكر المؤكدة
            foreach ($requestedSeatIds as $seatId) {
                if (in_array($seatId, $bookedSeatIds)) {
                    return ['valid' => false, 'message' => "المقعد رقم {$seatId} محجوز بالفعل في الرحلة {$trip->uuid}"];
                }
            }

            // تعديل: فحص جدول trip_seats للتأكد من حالة الـ Hold المؤقت والتحقق من التبعية والصلاحية
            $existingTripSeats = TripSeat::where('trip_id', $tripToCheck->id)
                ->whereIn('seat_id', $requestedSeatIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('seat_id');

            foreach ($requestedSeatIds as $seatId) {
                if (isset($existingTripSeats[$seatId])) {
                    $ts = $existingTripSeats[$seatId];
                    if ((int) $ts->trip_seat_status_id === TripSeatStatus::BOOKED) {
                        return ['valid' => false, 'message' => "المقعد رقم {$seatId} محجوز بالفعل في الرحلة {$trip->uuid}"];
                    }
                    if ((int) $ts->trip_seat_status_id === TripSeatStatus::BLOCKED) {
                        return ['valid' => false, 'message' => "المقعد رقم {$seatId} غير متاح للرحلة {$trip->uuid}"];
                    }
                    // فحص الـ Hold النشط
                    if ((int) $ts->trip_seat_status_id === TripSeatStatus::HELD) {
                        // إذا كان الـ Hold صالحاً (reserved_until > now()) ولم يكن يخص نفس المستخدم الممرر
                        if ($ts->reserved_until && $ts->reserved_until->isFuture()) {
                            if ($userId === null || (int) $ts->user_id !== (int) $userId) {
                                return ['valid' => false, 'message' => "المقعد رقم {$seatId} ممسوك مؤقتاً لعميل آخر في الرحلة {$trip->uuid}"];
                            }
                        }
                        // ملاحظة: إذا كان الـ Hold منتهياً (reserved_until <= now())، يُعتبر المقعد متاحاً تلقائياً ويمكن إعادة استخدامه!
                    }
                }
            }
        }

        return ['valid' => true, 'booked_seat_ids' => $bookedSeatIds];
    }

    /**
     * Create passenger tickets with atomic seat allocation
     *
     * This method creates tickets for all passengers while ensuring
     * atomic seat allocation to prevent double booking under high load.
     *
     * Security features:
     * - Atomic seat allocation with direct database locking
     * - Input validation and sanitization for all passenger data
     * - File upload handling with security checks
     * - Price consistency using pre-calculated amounts
     *
     * @param  TripReservation  $tripReservation  The trip reservation record
     * @param  Trip  $trip  The trip instance
     * @param  array  $passengersData  Array of passenger information
     * @param  float  $tripSpecificPrice  Pre-calculated price for this specific trip
     *
     * @throws \Exception When seat allocation or passenger creation fails
     */
    protected function createTickets(
        TripReservation $tripReservation,
        Trip $trip,
        array $passengersData,
        float $tripSpecificPrice,
        array $bookedSeatIds = []  // Pre-fetched booked seat IDs from validation
    ): void {
        // Normalize passenger data
        // Why: Ensure we always have valid passenger data
        $normalizedPassengerData = empty($passengersData)
            ? $this->generatePassengersList(1, 0)
            : $passengersData;

        // Calculate individual ticket price
        // Why: Distribute trip price evenly among all passengers
        $individualTicketPrice = $tripSpecificPrice / count($normalizedPassengerData);
        $tripVehicleId = $trip->vehicle_id;

        // Use pre-fetched booked seat IDs for consistency
        // Why: Ensure we use the same seat data that was validated in the locking phase
        $currentlyBookedSeatIds = $bookedSeatIds;

        foreach ($normalizedPassengerData as $passengerIndex => $individualPassengerData) {
            // Validate passenger name (critical business rule)
            // Why: Ensure data quality and prevent invalid entries
            if (empty($individualPassengerData['name']) || strlen($individualPassengerData['name']) < 3) {
                DB::rollBack();
                throw new \InvalidArgumentException('اسم المسافر يجب أن يكون 3 أحرف على الأقل');
            }

            // Create passenger record with sanitized data
            // Why: Prevent XSS and ensure data integrity
            $fullName = htmlspecialchars(strip_tags($individualPassengerData['name'] ?? 'مسافر'), ENT_QUOTES, 'UTF-8');
            $birthday = $individualPassengerData['birthday'] ?? null;

            $passengerRecord = $this->passengerRepository->findOrCreate([
                'uuid' => Str::uuid()->toString(),
                'created_by' => auth()->id(),
                'full_name' => $fullName,
                'birthday' => $birthday,
                'gender_id' => $individualPassengerData['gender_id'] ?? null,
                'phone_number' => $individualPassengerData['phone_number'] ?? null,
                'personal_card_id' => $individualPassengerData['personal_card_id'] ?? null,
                'personal_card_id_photo' => $individualPassengerData['personal_card_id_photo'] ?? null,
                'personal_country_place_of_issue' => $individualPassengerData['personal_country_place_of_issue'] ?? null,
                'personal_city_place_of_issue' => $individualPassengerData['personal_city_place_of_issue'] ?? null,
                'personal_card_date_issu' => $individualPassengerData['personal_card_date_issu'] ?? null,
                'personal_card_date_expiry' => $individualPassengerData['personal_card_date_expiry'] ?? null,
                'passport_number' => $individualPassengerData['passport_number'] ?? null,
                'passport_photo' => $individualPassengerData['passport_photo'] ?? null,
                'passport_country_place_of_issue' => $individualPassengerData['passport_country_place_of_issue'] ?? null,
                'passport_city_place_of_issue' => $individualPassengerData['passport_city_place_of_issue'] ?? null,
                'passport_date_issu' => $individualPassengerData['passport_date_issu'] ?? null,
                'passport_date_expiry' => $individualPassengerData['passport_date_expiry'] ?? null,
            ]);
            // Handle file uploads with security validation
            // Why: Move temporary files to permanent storage with proper validation
            $this->handlePassengerFileUploads($passengerRecord, $individualPassengerData);

            // Allocate seat atomically to prevent double booking
            // Why: Ensure seat assignment is conflict-free under high concurrency
            // الكود السابق معلق: $allocatedSeatId = $this->$allocatedSeatId = $this->allocateSeatAtomically(
            $allocatedSeatId = $this->allocateSeatAtomically(
                $trip->id,
                $tripVehicleId,
                $currentlyBookedSeatIds,
                $individualPassengerData['seat_id'] ?? null,
                $passengerRecord->created_by ?? auth()->id()
            );

            // Create ticket record with allocated seat
            // Why: Link passenger to specific seat with correct pricing
            $this->ticketRepository->create([
                'trip_reservation_id' => $tripReservation->id,
                'passenger_id' => $passengerRecord->id,
                'seat_id' => $allocatedSeatId,
                'ticket_number' => Str::random(10),
                'price_at_booking' => $individualTicketPrice,
                'status' => TicketStatus::ACTIVE,
            ]);

            // Update booked seats list to prevent reuse in same booking
            // Why: Maintain consistency within the same transaction
            $currentlyBookedSeatIds[] = $allocatedSeatId;

            // تعديل: قفل وتحديث سجل trip_seats وتحويل حالته من HELD إلى BOOKED وربطه برقم الحجز
            TripSeat::updateOrCreate(
                ['trip_id' => $trip->id, 'seat_id' => $allocatedSeatId],
                [
                    'trip_seat_status_id' => TripSeatStatus::BOOKED,
                    'booking_id' => $tripReservation->booking_id,
                    'user_id' => $passengerRecord->created_by ?? auth()->id(),
                    'reserved_until' => null,
                ]
            );
        }
    }

    /**
     * Handle passenger file uploads with security checks
     *
     * This method processes temporary file uploads and moves them to
     * permanent storage while validating file types and paths.
     *
     * @param  Passenger  $passenger  The passenger record
     * @param  array  $passengerData  Passenger data including file paths
     */
    private function handlePassengerFileUploads(Passenger $passenger, array $passengerData): void
    {
        $passengerId = $passenger->id;

        // Process personal card ID photo
        // Why: Handle identity document uploads securely
        if (! empty($passengerData['personal_card_id_photo']) && str_starts_with($passengerData['personal_card_id_photo'], 'temp/')) {
            $permanentPath = 'passengers/'.$passengerId.'/photos/'.basename($passengerData['personal_card_id_photo']);
            if (Storage::disk('public')->exists($passengerData['personal_card_id_photo'])) {
                Storage::disk('public')->move($passengerData['personal_card_id_photo'], $permanentPath);
                $passenger->update(['personal_card_id_photo' => $permanentPath]);
            }
        }

        // Process passport photo
        // Why: Handle passport uploads securely
        if (! empty($passengerData['passport_photo']) && str_starts_with($passengerData['passport_photo'], 'temp/')) {
            $permanentPath = 'passengers/'.$passengerId.'/photos/'.basename($passengerData['passport_photo']);
            if (Storage::disk('public')->exists($passengerData['passport_photo'])) {
                Storage::disk('public')->move($passengerData['passport_photo'], $permanentPath);
                $passenger->update(['passport_photo' => $permanentPath]);
            }
        }
    }

    /**
     * Allocate seat atomically to prevent double booking
     *
     * This method implements atomic seat allocation using direct database
     * locking to ensure no race conditions can occur under high load.
     *
     * Security features:
     * - Direct database locking with lockForUpdate()
     * - Ordered seat selection to prevent deadlocks
     * - Validation of seat ownership and availability
     * - Immediate seat reservation to prevent conflicts
     *
     * @param  int  $vehicleId  The vehicle ID containing the seats
     * @param  array  $alreadyBookedSeatIds  Array of already booked seat IDs
     * @param  int|null  $specificallyRequestedSeatId  Specific seat ID if requested by customer
     * @return int The allocated seat ID
     *
     * @throws \InvalidArgumentException When requested seat is not available
     * @throws \Exception When no seats are available
     */
    protected function allocateSeatAtomically( int $tripId, int $vehicleId, array $alreadyBookedSeatIds,
     ?int $specificallyRequestedSeatId = null,
        ?int $userId = null
    ): int {
        // إذا تم تحديد مقعد معين من العميل
        if ($specificallyRequestedSeatId) {

            // فحص المقاعد المحجوزة مسبقًا
            if (in_array($specificallyRequestedSeatId, $alreadyBookedSeatIds)) {
                throw new \InvalidArgumentException(
                    "المقعد {$specificallyRequestedSeatId} غير متاح أو تم حجزه"
                );
            }

            // قفل المقعد والتأكد أنه تابع لمركبة الرحلة
            $requestedSeat = Seat::where('vehicle_id', $vehicleId)
                ->where('id', $specificallyRequestedSeatId)
                ->lockForUpdate()
                ->first();

            if (! $requestedSeat) {
                throw new \InvalidArgumentException(
                    "المقعد {$specificallyRequestedSeatId} لا ينتمي لهذه المركبة"
                );
            }

            // فحص حالة المقعد على مستوى الرحلة
            $tripSeat = TripSeat::where('trip_id', $tripId)
                ->where('seat_id', $specificallyRequestedSeatId)
                ->lockForUpdate()
                ->first();

            if ($tripSeat) {

                // محجوز
                if ((int) $tripSeat->trip_seat_status_id === TripSeatStatus::BOOKED) {
                    throw new \InvalidArgumentException(
                        "المقعد {$specificallyRequestedSeatId} محجوز بالفعل"
                    );
                }

                // محظور
                if ((int) $tripSeat->trip_seat_status_id === TripSeatStatus::BLOCKED) {
                    throw new \InvalidArgumentException(
                        "المقعد {$specificallyRequestedSeatId} غير متاح"
                    );
                }

                // ممسوك مؤقتًا
                if ((int) $tripSeat->trip_seat_status_id === TripSeatStatus::HELD) {

                    if (
                        $tripSeat->reserved_until &&
                        $tripSeat->reserved_until->isFuture() &&
                        (int) $tripSeat->user_id !== (int) $userId
                    ) {
                        throw new \InvalidArgumentException(
                            "المقعد {$specificallyRequestedSeatId} ممسوك مؤقتًا بواسطة عميل آخر"
                        );
                    }
                }
            }

            return $requestedSeat->id;
        }

        // ============================================================
        // اختيار مقعد تلقائي
        // ============================================================

        // جلب المقاعد غير المتاحة على مستوى الرحلة
        $unavailableTripSeatIds = TripSeat::where('trip_id', $tripId)
            ->where(function ($query) use ($userId) {

                // BOOKED
                $query->where(
                    'trip_seat_status_id',
                    TripSeatStatus::BOOKED
                )

                // BLOCKED
                    ->orWhere(
                        'trip_seat_status_id',
                        TripSeatStatus::BLOCKED
                    )

                // HELD صالح لمستخدم آخر
                    ->orWhere(function ($query) use ($userId) {
                        $query->where(
                            'trip_seat_status_id',
                            TripSeatStatus::HELD
                        )
                            ->where('reserved_until', '>', now())
                            ->where(function ($query) use ($userId) {
                                $query->whereNull('user_id')
                                    ->orWhere('user_id', '!=', $userId);
                            });
                    });
            })
            ->lockForUpdate()
            ->pluck('seat_id')
            ->toArray();

        // دمج المقاعد المحجوزة مع المقاعد غير المتاحة
        $unavailableSeatIds = array_unique(array_merge(
            $alreadyBookedSeatIds,
            $unavailableTripSeatIds
        ));

        // اختيار أول مقعد متاح
        $availableSeat = Seat::where('vehicle_id', $vehicleId)
            ->when(
                ! empty($unavailableSeatIds),
                fn ($query) => $query->whereNotIn('id', $unavailableSeatIds)
            )
            ->orderBy('id')
            ->lockForUpdate()
            ->first();

        if (! $availableSeat) {
            throw new \InvalidArgumentException(
                'لا توجد مقاعد متاحة'
            );
        }

        return $availableSeat->id;
    }

    /**
     * تنفيذ الحجز المؤقت للمقاعد (Hold) لمدة 5 دقائق
     *
     * @param  string  $tripUuid  رمز الرحلة
     * @param  array  $seatIds  المقاعد المراد مسكها
     * @param  int  $userId  معرف المستخدم
     * @param  int  $durationMinutes  مدة المسك بالدقائق (افتراضياً 2 دقائق)
     * @return array نتيجة العملية
     */
    public function holdSeats(string $tripUuid, array $seatIds, int $userId, int $durationMinutes = 2): array
    {
        $trip = $this->tripRepository->findByUuid($tripUuid);
        if (! $trip) {
            return $this->error("الرحلة {$tripUuid} غير موجودة");
        }

        if (empty($seatIds)) {
            return $this->error('يجب تحديد مقعد واحد على الأقل للمسك المؤقت');
        }

        try {
            DB::beginTransaction();

            $lockedTrip = $this->tripRepository->lockForUpdate($trip->id);
            if (! $lockedTrip) {
                throw new \Exception("فشل قفل الرحلة {$tripUuid}");
            }

            // 1. التحقق أن المقاعد تنتمي لمركبة الرحلة
            $vehicleSeatIds = $lockedTrip->vehicle->seats()->pluck('id')->toArray();
            foreach ($seatIds as $seatId) {
                if (! in_array($seatId, $vehicleSeatIds)) {
                    DB::rollBack();

                    return $this->error("المقعد رقم {$seatId} لا ينتمي لمركبة هذه الرحلة");
                }
            }

            // 2. قفل فحص سجلات trip_seats لمنع السباق (Race Condition)
            $existingTripSeats = TripSeat::where('trip_id', $lockedTrip->id)
                ->whereIn('seat_id', $seatIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('seat_id');

            $bookedSeatIds = $lockedTrip->getBookedSeatIds();

            foreach ($seatIds as $seatId) {
                // فحص التذاكر المحجوزة
                if (in_array($seatId, $bookedSeatIds)) {
                    DB::rollBack();

                    return $this->error("المقعد رقم {$seatId} محجوز بالفعل بتذكرة مؤكدة");
                }

                if (isset($existingTripSeats[$seatId])) {
                    $ts = $existingTripSeats[$seatId];
                    if ((int) $ts->trip_seat_status_id === TripSeatStatus::BOOKED) {
                        DB::rollBack();

                        return $this->error("المقعد رقم {$seatId} محجوز بالفعل");
                    }
                    if ((int) $ts->trip_seat_status_id === TripSeatStatus::BLOCKED) {
                        DB::rollBack();

                        return $this->error("المقعد رقم {$seatId} غير متاح");
                    }
                    // فحص الـ Hold الصالح لمستخدم آخر
                    if ((int) $ts->trip_seat_status_id === TripSeatStatus::HELD) {
                        if ($ts->reserved_until && $ts->reserved_until->isFuture() && (int) $ts->user_id !== (int) $userId) {
                            DB::rollBack();

                            return $this->error("المقعد رقم {$seatId} ممسوك مؤقتاً بواسطة عميل آخر");
                        }
                    }
                }
            }

            // 3. تحديث / إعادة استخدام سجلات trip_seats وتخصيصها للمستخدم الجديد مع HELD لمدة 5 دقائق
            $reservedUntil = now()->addMinutes($durationMinutes);
            $heldSeats = [];

            foreach ($seatIds as $seatId) {
                $tripSeat = TripSeat::updateOrCreate(
                    ['trip_id' => $lockedTrip->id, 'seat_id' => $seatId],
                    [
                        'trip_seat_status_id' => TripSeatStatus::HELD,
                        'user_id' => $userId,
                        'booking_id' => null,
                        'reserved_until' => $reservedUntil,
                    ]
                );
                $heldSeats[] = [
                    'seat_id' => $tripSeat->seat_id,
                    'status' => 'held',
                    'reserved_until' => $reservedUntil->toIso8601String(),
                ];
            }

            DB::commit();

            return [
                'success' => true,
                'message' => "تم مسك المقاعد بنجاح لمدة {$durationMinutes} دقائق",
                'reserved_until' => $reservedUntil->toIso8601String(),
                'held_seats' => $heldSeats,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('فشل مسك المقاعد المؤقت', ['error' => $e->getMessage(), 'trip_uuid' => $tripUuid]);

            return $this->error('فشل تنفيذ الحجز المؤقت: '.$e->getMessage());
        }
    }

    /**
     * حساب سعر الرحلة
     */
    protected function calculateTripPrice(Trip $trip, array $passengersData): float
    {
        $adultsCount = 0;
        $childrenCount = 0;

        foreach ($passengersData as $passenger) {
            if (empty($passenger['birthday'])) {
                throw new \InvalidArgumentException('تاريخ ميلاد المسافر مطلوب');
            }

            $age = \Carbon\Carbon::parse($passenger['birthday'])->age;

            if ($age < 18) {
                $childrenCount++;
            } else {
                $adultsCount++;
            }
        }

        return ($adultsCount * $trip->price_for_adult) + ($childrenCount * $trip->price_for_child);
    }

    /**
     * توليد رحلة مكررة (Child) من الرحلة الأصلية (Parent) لتاريخ محدد
     *
     * ═══════════════════════════════════════════════════════════════════
     * المسؤوليات:
     * ═══════════════════════════════════════════════════════════════════
     * ✅ التحقق من صلاحية التاريخ ضمن جدول التكرار (business rule خاص بالحجز)
     * ✅ تفويض الإنشاء الفعلي إلى TripChildFactory
     *
     * كل منطق نسخ الحقول والعلاقات وحماية Race Condition
     * مُوكل إلى TripChildFactory — هذا المكان الوحيد الذي
     * يجب تعديله إذا أُضيف حقل جديد.
     *
     * @see TripChildFactory::createChildTrip() — المصدر الوحيد لإنشاء Child
     *
     * @param  Trip  $parent  الرحلة الأصلية (القالب)
     * @param  Carbon  $targetDate  التاريخ المطلوب إنشاء instance له
     * @return Trip|null الطفل المنشأ، أو null إذا لم يكن التاريخ صالحاً
     */
    protected function spawnTripFromParent(Trip $parent, Carbon $targetDate): ?Trip
    {
        // التحقق من أن التاريخ يتوافق مع أيام التكرار
        // (هذا الفحص خاص بسيناريو الحجز JIT — لا يوجد في الـ Factory)
        $dayName = WeekDays::toDatabaseValue(WeekDays::fromCarbonDay($targetDate->dayOfWeek));
        $repeat = $parent->repeats()
            ->where('status', 'active')
            ->whereDate('start_date', '<=', $targetDate)
            ->whereDate('end_date', '>=', $targetDate)
            ->whereHas('repeatedDays', function ($q) use ($dayName) {
                $q->where('day_of_week', $dayName);
            })->first();

        if (! $repeat) {
            return null;
        }

        // ── التفويض الكامل إلى TripChildFactory ──
        return $this->childFactory->createChildTrip($parent, $targetDate);
    }

    /**
     * Record accounting entries based on the configured accounting mode
     *
     * This method routes to the appropriate accounting implementation based on feature flags.
     *
     * @param  Booking  $booking  The booking record
     * @param  float  $amount  The amount to record
     * @param  int  $userId  The user ID creating the transaction
     * @param  TransactionReference  $transactionReference  The transaction reference
     * @param  array  $financialCalculation  Financial calculation details
     */
    protected function recordAccountingEntries(
        Booking $booking,
        float $amount,
        int $userId,
        TransactionReference $transactionReference,
        array $financialCalculation = []
    ): void {
        $this->recordBalancedBookingEntry($booking, $amount, $userId, $transactionReference, $financialCalculation);
    }

    protected function recordBalancedBookingEntry(
        Booking $booking,
        float $amount,
        int $userId,
        TransactionReference $transactionReference,
        array $financialCalculation = []
    ): void {
        $totalPaid = round((float) $amount, 2);
        $taxAmount = round((float) $booking->taxes->sum('tax_amount'), 2);
        $commission = round((float) ($financialCalculation['commission_amount'] ?? 0), 2);
        $commission = round($commission * ($booking->exchange_rate ?: 1), 2);
        $providerAmount = round($totalPaid - $commission - $taxAmount, 2);

        if ($providerAmount < 0) {
            Log::critical('UNBALANCED_BOOKING_DETECTED', [
                'booking_uuid' => $booking->uuid,
                'totalPaid' => $totalPaid,
                'commission' => $commission,
                'taxAmount' => $taxAmount,
                'providerAmount' => $providerAmount,
            ]);

            throw new \RuntimeException('خطأ في حساب مستحقات المزود - يرجى مراجعة إعدادات العمولة والضرائب');
        }

        $rate = $booking->exchange_rate ?: 1;
        $baseTotalPaid = round($totalPaid / $rate, 2);
        $baseProviderAmount = round($providerAmount / $rate, 2);
        $baseTaxAmount = round($taxAmount / $rate, 2);
        $baseCommission = round($commission / $rate, 2);

        $providerId = $booking->tripReservations->first()?->trip?->vehicle?->user_id;
        if (! $providerId) {
            Log::warning('Provider not found for booking, falling back to LIABILITIES', [
                'booking_id' => $booking->id,
            ]);
            $providerCode = AccountCodes::LIABILITIES;
        } else {
            $providerCode = AccountCodes::providerAccount($providerId);
        }
        $bookingWithRelations = $this->bookingRepository->findByUuid($booking->uuid, ['user:id,name']);
        $customerName = $bookingWithRelations?->user?->name ?? 'عميل غير مسجل';
        $bookingShortId = '#'.$booking->id;

        $commonData = [
            'transaction_reference_id' => $transactionReference->id,
            'currency_id' => $booking->currency_id,
            'exchange_rate' => $booking->exchange_rate,
        ];

        // 1. العميل يدفع (مدين)
        $this->transactionRepository->createTransaction(array_merge($commonData, [
            'account_code' => AccountCodes::ASSETS,
            'transaction_type' => 'debit',
            'amount' => $totalPaid,
            'base_amount' => $baseTotalPaid,
            'description' => "حجز رحلة للعميل {$customerName} - رقم الحجز {$bookingShortId}",
        ]));

        // 2. التزام لمزود الخدمة (دائن)
        $this->transactionRepository->createTransaction(array_merge($commonData, [
            'account_code' => $providerCode,
            'transaction_type' => 'credit',
            'amount' => $providerAmount,
            'base_amount' => $baseProviderAmount,
            'description' => "مستحقات مزود الخدمة - حجز {$bookingShortId} (العميل: {$customerName})",
        ]));

        // 3. التزام ضريبي (دائن)
        if ($taxAmount > 0) {
            $this->transactionRepository->createTransaction(array_merge($commonData, [
                'account_code' => AccountCodes::LIABILITIES,
                'transaction_type' => 'credit',
                'amount' => $taxAmount,
                'base_amount' => $baseTaxAmount,
                'description' => "ضريبة قيمة مضافة - حجز {$bookingShortId} (العميل: {$customerName})",
            ]));
        }

        // 4. إيراد المنصة (دائن)
        if ($commission > 0) {
            $this->transactionRepository->createTransaction(array_merge($commonData, [
                'account_code' => AccountCodes::REVENUE,
                'transaction_type' => 'credit',
                'amount' => $commission,
                'base_amount' => $baseCommission,
                'description' => "عمولة المنصة - حجز {$bookingShortId} (العميل: {$customerName})",
            ]));
        }

        if (! $this->transactionRepository->validateEntryBalance($transactionReference->id)) {
            throw new \RuntimeException("خطأ محاسبي: القيد غير متوازن للمرجع {$transactionReference->id}");
        }
    }

    /**
     * Verifies that total debits equal total credits for a transaction reference.
     * Logs critical error if entries are unbalanced.
     *
     * @param  int  $referenceId  Transaction reference ID
     *
     * @throws \RuntimeException If entries are unbalanced
     */
    protected function validateEntryBalance(int $referenceId): void
    {
        $debits = Transaction::where('transaction_reference_id', $referenceId)
            ->where('transaction_type', 'debit')
            ->sum('amount');

        $credits = Transaction::where('transaction_reference_id', $referenceId)
            ->where('transaction_type', 'credit')
            ->sum('amount');

        $difference = abs($debits - $credits);

        if ($difference > 0.01) {
            Log::critical('UNBALANCED ACCOUNTING ENTRY DETECTED!', [
                'reference_id' => $referenceId,
                'debits' => $debits,
                'credits' => $credits,
                'difference' => $difference,
                'service' => 'BookingService',
                'timestamp' => now()->toDateTimeString(),
            ]);

            throw new \RuntimeException(
                "Unbalanced accounting entry detected. Reference: {$referenceId}, Difference: {$difference}"
            );
        }
    }

    private function prepareSeatsForValidation(array $passengersData, array $tripUuids, string $currentTripUuid): array
    {
        if (count($tripUuids) <= 1) {
            return $passengersData;
        }

        $tripDirection = ($currentTripUuid === $tripUuids[0]) ? 'outbound' : 'return';
        $field = $tripDirection === 'outbound' ? 'outbound_seat_id' : 'return_seat_id';

        $prepared = [];
        foreach ($passengersData as $passenger) {
            $passengerCopy = $passenger;
            $passengerCopy['seat_id'] = $passenger[$field] ?? null;
            $prepared[] = $passengerCopy;
        }

        return $prepared;
    }

    /**
     * جلب قائمة بالمسافرين السابقين للمستخدم
     *
     * This method retrieves a list of passengers previously created by the user or associated with their bookings.
     * It allows users to quickly select from their past passengers when making a new booking.
     *
     * @param  int  $userId  The ID of the user
     * @param  int  $limit  The maximum number of passengers to retrieve
     * @return Collection A collection of Passenger models
     */
    public function getUserPreviousPassengers(int $userId, int $limit = 10): Collection
    {
        return Passenger::where('created_by', $userId)
            ->orWhereHas('tripReservations.booking', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->select('id', 'full_name', 'birthday', 'gender_id', 'phone_number', 'personal_card_id')
            ->distinct()
            ->latest()
            ->take($limit)
            ->get();
    }

    /**
     * تأكيد صعود مسافر واحد (تذكرة واحدة) إلى الباص
     */
    public function confirmBoarding(string $ticketUuid, int $actorId): array
    {
        $actor = User::find($actorId);

        if (! $actor) {
            return ['success' => false, 'message' => 'المستخدم غير موجود', 'code' => 403];
        }

        try {
            DB::beginTransaction();

            $ticket = Ticket::where('uuid', $ticketUuid)
                ->with([
                    'tripReservation.trip.vehicle',
                    'tripReservation.booking',
                    'tripReservation.trip.departureCity',
                    'tripReservation.trip.destinationCity',
                    'passenger',
                    'seat',
                ])
                ->lockForUpdate()
                ->first();

            if (! $ticket) {
                DB::rollBack();

                return ['success' => false, 'message' => 'التذكرة غير موجودة', 'code' => 404];
            }

            $trip = $ticket->tripReservation?->trip;
            $booking = $ticket->tripReservation?->booking;

            // 2. التحقق من الصلاحية
            if (! $actor->isAdmin()) {
                $providerId = $actor->isProviderOwner() ? $actor->id : $actor->user_parent;
                if (! $trip || $trip->vehicle?->user_id !== $providerId) {
                    DB::rollBack();

                    return ['success' => false, 'message' => 'غير مصرح لك بتأكيد الصعود لهذه التذكرة', 'code' => 403];
                }
            }

            // 3. التحقق من حالة الحجز والتذكرة (داخل القفل)
            if ($booking && in_array($booking->booking_status_id, [BookingStatus::CANCELLED, BookingStatus::CANCELLED_BY_USER, BookingStatus::CANCELLED_BY_ADMIN])) {
                DB::rollBack();

                return ['success' => false, 'message' => 'لا يمكن تأكيد الصعود لتذكرة في حجز ملغي', 'code' => 400];
            }

            if ($ticket->status === TicketStatus::CANCELLED) {
                DB::rollBack();

                return ['success' => false, 'message' => 'هذه التذكرة ملغية', 'code' => 400];
            }

            if ($ticket->boarded_at !== null || $ticket->status === TicketStatus::BOARDED) {
                DB::rollBack();

                return ['success' => false, 'message' => 'المسافر صعد للباص بالفعل', 'code' => 400];
            }

            // 4. تنفيذ تأكيد الصعود
            $this->ticketRepository->update($ticket, [
                'status' => TicketStatus::BOARDED,
                'boarded_at' => now(),
                'boarded_by' => $actorId,
            ]);

            $this->auditService->log($actorId, 'boarding_confirmed', 'tickets', $ticket->id, null, ['boarded_at' => now()]);

            DB::commit();

            $passenger = $ticket->passenger()->withoutGlobalScopes()->first();
            $seat = $ticket->seat;

            return [
                'success' => true,
                'message' => 'تم تأكيد صعود المسافر بنجاح',
                'data' => [
                    'ticket' => [
                        'uuid' => $ticket->uuid,
                        'id' => $ticket->id,
                        'ticket_number' => $ticket->ticket_number,
                        'price_at_booking' => $ticket->price_at_booking,
                        'status' => $ticket->status,
                        'boarded_at' => $ticket->boarded_at ? $ticket->boarded_at->format('Y-m-d H:i:s') : null,
                        'boarded_by_name' => $ticket->boardedBy?->name ?? 'N/A',
                        'seat' => $seat ? [
                            'id' => $seat->id,
                            'name' => $seat->seat_name,
                        ] : null,
                        'passenger' => $passenger ? [
                            'id' => $passenger->id,
                            'name' => $passenger->full_name,
                            'birthday' => $passenger->birthday ? $passenger->birthday->format('Y-m-d') : null,
                        ] : null,
                    ],
                ],
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Ticket boarding failed: '.$e->getMessage());

            return ['success' => false, 'message' => 'فشل تأكيد الصعود. يرجى المحاولة مرة أخرى.', 'code' => 500];
        }
    }

    public function getMyBoardedTickets(int $actorId): array
    {
        $actor = User::find($actorId);

        if (! $actor) {
            return ['success' => false, 'message' => 'المستخدم غير موجود', 'code' => 403];
        }

        if (! $actor->isAdmin() && ! $actor->isProvider() && ! $actor->isProviderStaff()) {
            return ['success' => false, 'message' => 'هذه الميزة مخصصة للمزودين وموظفيهم فقط', 'code' => 403];
        }

        $tickets = $this->ticketRepository->getBoardedTicketsByUser($actorId);

        $data = $tickets->map(function ($ticket) {
            $passenger = $ticket->passenger()->withoutGlobalScopes()->first();

            return [
                'uuid' => $ticket->uuid,
                'id' => $ticket->id,
                'boarded_by' => $ticket->boardedBy?->name ?? 'N/A',
                'ticket_number' => $ticket->ticket_number,
                'price_at_booking' => $ticket->price_at_booking,
                'status' => $ticket->status,
                'boarded_at' => $ticket->boarded_at ? $ticket->boarded_at->format('Y-m-d H:i:s') : null,
                'seat' => $ticket->seat ? [
                    'id' => $ticket->seat->id,
                    'name' => $ticket->seat->seat_name,
                ] : null,
                'passenger' => $passenger ? [
                    'id' => $passenger->id,
                    'name' => $passenger->full_name,
                    'birthday' => $passenger->birthday ? $passenger->birthday->format('Y-m-d') : null,
                ] : null,
                'trip' => [
                    'from' => $ticket->tripReservation?->trip?->departureCity?->name ?? 'N/A',
                    'to' => $ticket->tripReservation?->trip?->destinationCity?->name ?? 'N/A',
                ],
            ];
        });

        return [
            'success' => true,
            'data' => $data,
        ];
    }

    private function processWalletPayment(int $userId, int $currencyId, float $amount, ?int $bookingId = null): string
    {
        $walletAccount = $this->walletService->getOrCreateAccount($userId, $currencyId);
        $walletTransaction = $this->walletService->debit(
            $walletAccount,
            $amount,
            'حجز رحلة',
            'Booking',
            $bookingId
        );

        return $walletTransaction->uuid;
    }

    private function processGatewayPayment(int $paymentMethodId, float $amount, ?string $stripeToken): string
    {
        $paymentResult = $this->paymentGatewayService->processPayment([
            'payment_method_id' => $paymentMethodId,
            'amount' => $amount,
            'stripe_token' => $stripeToken,
        ]);

        if (! $paymentResult['success']) {
            throw new \Exception('فشل الدفع عبر البوابة: '.$paymentResult['message']);
        }

        return $paymentResult['transaction_id'];
    }
}
