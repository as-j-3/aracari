<?php

namespace App\Http\Controllers\Admin;

use App\Constants\AccountCodes;
use App\Constants\BookingStatus;
use App\Constants\TransactionReferenceStatus;
use App\Constants\TripSeatStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserDirectPaymentInfoResource;
use App\Models\Account;
use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\TransactionReference;
use App\Models\Trip;
use App\Models\TripSeat;
use App\Models\User;
use App\Models\UserDirectPaymentInfo;
use App\Services\AuditService;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DirectTransferPaymentAdminController extends Controller
{
    public function __construct(
        private AuditService $auditService,
        private BookingService $bookingService

    ) {
        $this->middleware('require.admin');
    }

    /**
     * عرض جميع التحويلات المباشرة المرسلة.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min(
            max((int) $request->input('per_page', 15), 1),
            100
        );

        $query = UserDirectPaymentInfo::query()
            ->with([
                'directTransferPayment.paymentMethod',
                'bookingPayment.booking.user',
            ])
            ->latest();

        /*
        |--------------------------------------------------------------------------
        | البحث
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {
            $search = trim($request->input('search'));

            $query->where(function ($q) use ($search) {

                $q->where('receipt_number', 'like', "%{$search}%")
                    ->orWhere('sender_name', 'like', "%{$search}%")

                    ->orWhereHas(
                        'bookingPayment.booking',
                        function ($bookingQuery) use ($search) {
                            $bookingQuery
                                ->where('uuid', 'like', "%{$search}%")
                                ->orWhereHas(
                                    'user',
                                    function ($userQuery) use ($search) {
                                        $userQuery->where(
                                            'name',
                                            'like',
                                            "%{$search}%"
                                        );
                                    }
                                );
                        }
                    );
            });
        }

        /*
        |--------------------------------------------------------------------------
        | فلترة حالة الدفع
        |--------------------------------------------------------------------------
        */

        if ($request->filled('paid_status')) {
            $query->whereHas(
                'bookingPayment',
                function ($paymentQuery) use ($request) {
                    $paymentQuery->where(
                        'paid_status',
                        $request->input('paid_status')
                    );
                }
            );
        }

        $payments = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'تم جلب التحويلات المباشرة بنجاح',
            'data' => UserDirectPaymentInfoResource::collection($payments),
            'meta' => [
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
            ],
        ]);
    }

    /**
     * اعتماد التحويل وتأكيد الحجز.
     */
    public function approve(string $uuid): JsonResponse
    {
        try {

            /*
            |--------------------------------------------------------------------------
            | المرحلة 1: جميع التحققات بدون تعديل
            |--------------------------------------------------------------------------
            */

            $paymentInfo = UserDirectPaymentInfo::query()
                ->where('uuid', $uuid)
                ->with([
                    'bookingPayment.booking',
                    'directTransferPayment',
                ])
                ->first();

            if (! $paymentInfo) {
                return $this->error(
                    'بيانات التحويل غير موجودة',
                    null,
                    404
                );
            }

            $bookingPayment = $paymentInfo->bookingPayment;

            if (! $bookingPayment) {
                return $this->error(
                    'عملية الدفع غير موجودة',
                    null,
                    404
                );
            }

            $booking = $bookingPayment->booking;

            if (! $booking) {
                return $this->error(
                    'الحجز غير موجود',
                    null,
                    404
                );
            }

            /*
            |----------------------------------------------------------------------
            | التحقق من حالة الدفع
            |----------------------------------------------------------------------
            */

            if ($bookingPayment->paid_status !== 'pending') {
                return $this->error(
                    'عملية الدفع تمت معالجتها مسبقاً',
                    null,
                    422
                );
            }

            /*
            |----------------------------------------------------------------------
            | التحقق من حالة الحجز
            |----------------------------------------------------------------------
            */

            if (
                (int) $booking->booking_status_id !==
                BookingStatus::PENDING
            ) {
                return $this->error(
                    'حالة الحجز لا تسمح بالاعتماد',
                    null,
                    422
                );
            }

            /*
            |----------------------------------------------------------------------
            | جلب المقاعد
            |----------------------------------------------------------------------
            */

            $tripSeats = TripSeat::query()
                ->where('booking_id', $booking->id)
                ->get();

            if ($tripSeats->isEmpty()) {
                return $this->error(
                    'لا توجد مقاعد مرتبطة بالحجز',
                    null,
                    422
                );
            }

            /*
            |----------------------------------------------------------------------
            | التحقق من أن جميع المقاعد BOOKED
            |----------------------------------------------------------------------
            */

            $invalidSeat = $tripSeats->first(
                fn ($seat) => (int) $seat->trip_seat_status_id !==
                    TripSeatStatus::BOOKED
            );

            if ($invalidSeat) {
                return $this->error(
                    'يوجد مقعد حالته غير صحيحة لهذا الحجز',
                    null,
                    422
                );
            }

            /*
            |----------------------------------------------------------------------
            | التحقق من المزود
            |----------------------------------------------------------------------
            */

            $providerId = $booking
                ->tripReservations
                ->first()?->trip
                ?->vehicle
                ?->user_id;

            if (! $providerId) {
                return $this->error(
                    'تعذر تحديد مزود الخدمة',
                    null,
                    422
                );
            }

            /*
            |----------------------------------------------------------------------
            | التحقق من حساب المزود
            |----------------------------------------------------------------------
            */

            $providerUser = User::find($providerId);

            if (! $providerUser || empty($providerUser->account_code)) {
                return $this->error(
                    'الحساب المحاسبي لمزود الخدمة غير مرتبط بالمستخدم',
                    null,
                    422
                );
            }

            $providerAccount = Account::where(
                'account_code',
                $providerUser->account_code
            )->first();

            if (! $providerAccount) {
                return $this->error(
                    'الحساب المحاسبي لمزود الخدمة غير موجود',
                    null,
                    422
                );
            }
            /*
            |--------------------------------------------------------------------------
            | حسابات القيد في الذاكرة
            |--------------------------------------------------------------------------
            |
            | هنا نحسب القيد المتوقع أولاً.
            | لا ننشئ أي TransactionReference أو Transaction حتى الآن.
            |
            */

            $totalPaid = round(
                (float) $bookingPayment->payed_amount,
                2
            );

            $commission = round(
                (float) $booking->commission_amount,
                2
            );

            $providerAmount = round(
                $totalPaid - $commission,
                2
            );

            if ($providerAmount < 0) {
                return $this->error(
                    'خطأ في حساب مستحق المزود',
                    null,
                    422
                );
            }

            /*
            |--------------------------------------------------------------------------
            | التحقق من توازن القيد المتوقع
            |--------------------------------------------------------------------------
            */

            $totalDebit = $totalPaid;

            $totalCredit =
                $providerAmount +
                $commission;

            if (
                abs($totalDebit - $totalCredit) >
                0.01
            ) {
                return $this->error(
                    'القيد المحاسبي المتوقع غير متوازن',
                    null,
                    422
                );
            }

            /*
            |--------------------------------------------------------------------------
            | المرحلة 2: بعد نجاح جميع التحققات
            |--------------------------------------------------------------------------
            */

            DB::beginTransaction();

            /*
            |--------------------------------------------------------------------------
            | قفل السجلات مرة ثانية قبل التعديل
            |--------------------------------------------------------------------------
            */

            $paymentInfo = UserDirectPaymentInfo::query()
                ->where('id', $paymentInfo->id)
                ->lockForUpdate()
                ->first();

            $bookingPayment = BookingPayment::query()
                ->where('id', $bookingPayment->id)
                ->lockForUpdate()
                ->first();

            $booking = Booking::query()
                ->where('id', $booking->id)
                ->lockForUpdate()
                ->first();

            /*
            |--------------------------------------------------------------------------
            | إعادة التحقق بعد القفل
            |--------------------------------------------------------------------------
            */

            if (
                $bookingPayment->paid_status !== 'pending' ||
                (int) $booking->booking_status_id !== BookingStatus::PENDING
            ) {
                throw new \RuntimeException(
                    'تم تغيير حالة العملية أثناء المعالجة'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | تحديث حالة الدفع
            |--------------------------------------------------------------------------
            */

            $bookingPayment->update([
                'paid_status' => 'paid',
                'verified_by_user' => auth()->id(),
                'verified_at' => now(),
                'reject_reason' => null,
            ]);

            /*
            |--------------------------------------------------------------------------
            | تأكيد الحجز
            |--------------------------------------------------------------------------
            */

            $booking->update([
                'booking_status_id' => BookingStatus::CONFIRMED,
            ]);

            /*
            |--------------------------------------------------------------------------
            | إنشاء المرجع المحاسبي
            |--------------------------------------------------------------------------
            */

            $transactionReference = TransactionReference::create([
                'reference_number' => 'BOOK-'.strtoupper(Str::random(8)),

                'reference_type' => 'booking',

                'reference_id' => $booking->id,

                'created_by' => auth()->id(),

                'status' => TransactionReferenceStatus::DRAFT,

                'description' => 'اعتماد تحويل مباشر للحجز - '.
                    $booking->uuid,
            ]);

            /*
            |--------------------------------------------------------------------------
            | إنشاء القيود
            |--------------------------------------------------------------------------
            */

            // مدين: المبلغ المستلم
            // دائن: مستحق المزود
            // دائن: عمولة أراكاري

            // إنشاء القيد المحاسبي عبر الـ helper
            // (التي تتحقق من توازن القيد داخلياً في نهايتها)

            $this->createBookingAccountingEntry(
                $booking,
                $bookingPayment,
                $transactionReference
            );

            DB::commit();

            /*
            |--------------------------------------------------------------------------
            | Audit بعد النجاح
            |--------------------------------------------------------------------------
            */

            $this->auditService->log(
                auth()->id(),
                'approve_direct_transfer',
                'booking_payments',
                $bookingPayment->id,
                [
                    'paid_status' => 'pending',
                    'booking_status_id' => BookingStatus::PENDING,
                ],
                [
                    'paid_status' => 'paid',
                    'booking_status_id' => BookingStatus::CONFIRMED,
                    'verified_by_user' => auth()->id(),
                    'verified_at' => now()->toDateTimeString(),
                    'transaction_reference_id' => $transactionReference->id,
                ]
            );

            return $this->success(
                [
                    'booking_uuid' => $booking->uuid,
                    'booking_payment_id' => $bookingPayment->id,
                    'payment_status' => 'paid',
                    'booking_status' => BookingStatus::CONFIRMED,
                    'transaction_reference_id' => $transactionReference->id,
                ],
                'تم اعتماد الحوالة وتأكيد الحجز وإنشاء القيد المحاسبي'
            );

        } catch (\Throwable $e) {

            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            Log::error(
                'Failed to approve direct transfer payment',
                [
                    'uuid' => $uuid,
                    'admin_id' => auth()->id(),
                    'error' => $e->getMessage(),
                ]
            );

            return $this->error(
                'لم يتم اعتماد الحوالة. لم يتم حفظ أي تغييرات.',
                null,
                500
            );
        }
    }

    /**
     * رفض التحويل وإلغاء الحجز وتحرير المقاعد.
     */
    public function reject(
        string $uuid,
        Request $request
    ): JsonResponse {
        $validated = $request->validate([
            'reject_reason' => [
                'required',
                'string',
                'max:1000',
            ],
        ]);

        try {
            DB::beginTransaction();

            /*
            |--------------------------------------------------------------------------
            | 1. جلب بيانات التحويل
            |--------------------------------------------------------------------------
            */

            $paymentInfo = UserDirectPaymentInfo::query()
                ->where('uuid', $uuid)
                ->lockForUpdate()
                ->first();

            if (! $paymentInfo) {
                DB::rollBack();

                return $this->error(
                    'بيانات التحويل غير موجودة',
                    null,
                    404
                );
            }

            /*
            |--------------------------------------------------------------------------
            | 2. جلب عملية الدفع
            |--------------------------------------------------------------------------
            */

            $bookingPayment = BookingPayment::query()
                ->where('id', $paymentInfo->booking_payment_id)
                ->lockForUpdate()
                ->first();

            if (! $bookingPayment) {
                DB::rollBack();

                return $this->error(
                    'عملية الدفع غير موجودة',
                    null,
                    404
                );
            }

            /*
            |--------------------------------------------------------------------------
            | 3. منع الرفض المتكرر
            |--------------------------------------------------------------------------
            */

            if ($bookingPayment->paid_status === 'paid') {
                DB::rollBack();

                return $this->error(
                    'لا يمكن رفض عملية دفع تم اعتمادها مسبقاً',
                    null,
                    422
                );
            }

            if ($bookingPayment->paid_status === 'rejected') {
                DB::rollBack();

                return $this->error(
                    'تم رفض عملية الدفع مسبقاً',
                    null,
                    422
                );
            }

            /*
            |--------------------------------------------------------------------------
            | 4. جلب الحجز
            |--------------------------------------------------------------------------
            */

            $booking = Booking::query()
                ->where('id', $bookingPayment->booking_id)
                ->lockForUpdate()
                ->first();

            if (! $booking) {
                DB::rollBack();

                return $this->error(
                    'الحجز غير موجود',
                    null,
                    404
                );
            }

            /*
            |--------------------------------------------------------------------------
            | 5. تحديث حالة الدفع
            |--------------------------------------------------------------------------
            */

            $bookingPayment->update([
                'paid_status' => 'rejected',
                'verified_by_user' => auth()->id(),
                'verified_at' => now(),
                'reject_reason' => $validated['reject_reason'],
            ]);

            /*
            |--------------------------------------------------------------------------
            | 6. إلغاء الحجز
            |--------------------------------------------------------------------------
            */

            $booking->update([
                'booking_status_id' => BookingStatus::CANCELLED,
            ]);

            /*
            |--------------------------------------------------------------------------
            | 7. تحرير المقاعد
            |--------------------------------------------------------------------------
            */

            $tripSeats = TripSeat::query()
                ->where('booking_id', $booking->id)
                ->where(
                    'trip_seat_status_id',
                    TripSeatStatus::BOOKED
                )
                ->lockForUpdate()
                ->get();

            /*
            |--------------------------------------------------------------------------
            | تجميع المقاعد حسب الرحلة
            |--------------------------------------------------------------------------
            */

            $seatsByTrip = $tripSeats
                ->groupBy('trip_id');

            foreach ($seatsByTrip as $tripId => $seats) {

                $seatCount = $seats->count();

                /*
                |--------------------------------------------------------------------------
                | تحرير المقاعد
                |--------------------------------------------------------------------------
                */

                foreach ($seats as $tripSeat) {
                    $tripSeat->update([
                        'trip_seat_status_id' => TripSeatStatus::AVAILABLE,

                        'booking_id' => null,

                        'user_id' => null,

                        'reserved_until' => null,
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | إعادة المقاعد لعداد الرحلة
                |--------------------------------------------------------------------------
                */

                $trip = Trip::query()
                    ->where('id', $tripId)
                    ->lockForUpdate()
                    ->first();

                if ($trip) {
                    $trip->increment(
                        'available_seats',
                        $seatCount
                    );

                    $trip->clearSeatsCache();
                }
            }

            /*
            |--------------------------------------------------------------------------
            | 8. تثبيت العملية
            |--------------------------------------------------------------------------
            */

            DB::commit();

            /*
            |--------------------------------------------------------------------------
            | 9. Audit
            |--------------------------------------------------------------------------
            */

            $this->auditService->log(
                auth()->id(),
                'reject_direct_transfer',
                'booking_payments',
                $bookingPayment->id,
                [
                    'paid_status' => 'pending',
                    'booking_status_id' => BookingStatus::PENDING,
                ],
                [
                    'paid_status' => 'rejected',
                    'verified_by_user' => auth()->id(),
                    'verified_at' => now()->toDateTimeString(),
                    'reject_reason' => $validated['reject_reason'],
                    'booking_status_id' => BookingStatus::CANCELLED,
                ]
            );

            return $this->success(
                [
                    'booking_uuid' => $booking->uuid,
                    'booking_payment_id' => $bookingPayment->id,
                    'payment_status' => $bookingPayment->paid_status,
                    'booking_status' => $booking->booking_status_id,
                    'released_seats' => $tripSeats->count(),
                ],
                'تم رفض الحوالة وإلغاء الحجز وتحرير المقاعد'
            );

        } catch (\Throwable $e) {

            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            Log::error(
                'Failed to reject direct transfer payment',
                [
                    'uuid' => $uuid,
                    'admin_id' => auth()->id(),
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]
            );

            return $this->error(
                'حدث خطأ أثناء رفض الحوالة',
                null,
                500
            );
        }
    }

    /**
     * إنشاء القيد المحاسبي للحجز المعتمد.
     */
    private function createBookingAccountingEntry(
        Booking $booking,
        BookingPayment $bookingPayment,
        TransactionReference $transactionReference
    ): void {
        $totalPaid = round(
            (float) $bookingPayment->payed_amount,
            2
        );

        /*
        |--------------------------------------------------------------------------
        | العمولة
        |--------------------------------------------------------------------------
        */

        $commission = round(
            (float) $booking->commission_amount,
            2
        );

        $exchangeRate =
            (float) (
                $booking->exchange_rate ?: 1
            );

        $baseTotalPaid = round(
            $totalPaid / $exchangeRate,
            2
        );

        $baseCommission = round(
            $commission / $exchangeRate,
            2
        );

        /*
        |--------------------------------------------------------------------------
        | مستحق المزود
        |--------------------------------------------------------------------------
        |
        | ضريبة التذكرة ضمن مستحق المزود.
        |
        */

        $providerAmount = round(
            $totalPaid - $commission,
            2
        );

        $baseProviderAmount = round(
            $providerAmount / $exchangeRate,
            2
        );

        if ($providerAmount < 0) {
            throw new \RuntimeException(
                'خطأ في حساب مستحق المزود'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | تحديد حساب المزود
        |--------------------------------------------------------------------------
        */

        $providerId = $booking
            ->tripReservations
            ->first()?->trip
            ?->vehicle
            ?->user_id;

        if (! $providerId) {
            throw new \RuntimeException(
                'تعذر تحديد حساب مزود الخدمة'
            );
        }

        $providerUser = User::find($providerId);

        if (! $providerUser || empty($providerUser->account_code)) {
            throw new \RuntimeException(
                'الحساب المحاسبي لمزود الخدمة غير مرتبط بالمستخدم'
            );
        }

        $providerCode = $providerUser->account_code;

        /*
        |--------------------------------------------------------------------------
        | العميل / النقدية
        |--------------------------------------------------------------------------
        */

        $commonData = [
            'transaction_reference_id' => $transactionReference->id,

            'currency_id' => $bookingPayment->currency_id,

            'exchange_rate' => $bookingPayment->exchange_rate,
        ];

        /*
        |--------------------------------------------------------------------------
        | 1. إجمالي التحويل - مدين
        |--------------------------------------------------------------------------
        */

        $this->bookingService
            ->transactionRepository
            ->createTransaction(
                array_merge(
                    $commonData,
                    [
                        'account_code' => AccountCodes::ASSETS,

                        'transaction_type' => 'debit',

                        'amount' => $totalPaid,

                        'base_amount' => $baseTotalPaid,

                        'description' => "استلام تحويل مباشر للحجز #{$booking->id}",
                    ]
                )
            );

        /*
        |--------------------------------------------------------------------------
        | 2. مستحق المزود - دائن
        |--------------------------------------------------------------------------
        */

        $this->bookingService
            ->transactionRepository
            ->createTransaction(
                array_merge(
                    $commonData,
                    [
                        'account_code' => $providerCode,

                        'transaction_type' => 'credit',

                        'amount' => $providerAmount,

                        'base_amount' => $baseProviderAmount,

                        'description' => "مستحق مزود الخدمة - الحجز #{$booking->id}",
                    ]
                )
            );

        /*
        |--------------------------------------------------------------------------
        | 3. إيراد العمولة - دائن
        |--------------------------------------------------------------------------
        */

        if ($commission > 0) {

            $this->bookingService
                ->transactionRepository
                ->createTransaction(
                    array_merge(
                        $commonData,
                        [
                            'account_code' => AccountCodes::REVENUE,

                            'transaction_type' => 'credit',

                            'amount' => $commission,

                            'base_amount' => $baseCommission,

                            'description' => "عمولة أراكاري - الحجز #{$booking->id}",
                        ]
                    )
                );
        }

        /*
        |--------------------------------------------------------------------------
        | التحقق من توازن القيد
        |--------------------------------------------------------------------------
        */

        if (
            ! $this->bookingService
                ->transactionRepository
                ->validateEntryBalance(
                    $transactionReference->id
                )
        ) {
            throw new \RuntimeException(
                "القيد المحاسبي غير متوازن للمرجع {$transactionReference->id}"
            );
        }
    }

    protected function success(
        mixed $data = null,
        string $message = 'تمت العملية بنجاح',
        int $statusCode = 200
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }

    protected function error(
        string $message,
        mixed $errors = null,
        int $statusCode = 400
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'data' => null,
        ], $statusCode);
    }
}
