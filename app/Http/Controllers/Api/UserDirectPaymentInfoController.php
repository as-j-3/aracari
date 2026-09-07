<?php

namespace App\Http\Controllers\Api;

use App\Constants\BookingStatus;
use App\Constants\TripSeatStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Payment\StoreUserDirectPaymentInfoRequest;
use App\Http\Resources\UserDirectPaymentInfoResource;
use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\DirectTransferPayment;
use App\Models\TripSeat;
use App\Models\UserDirectPaymentInfo;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UserDirectPaymentInfoController extends Controller
{
    public function __construct(
        private AuditService $auditService
    ) {}

    /**
     * تسجيل بيانات التحويل المباشر وإنشاء عملية الدفع.
     *
     * عند نجاح العملية:
     * - يتم إنشاء BookingPayment بحالة pending
     * - يتم إنشاء UserDirectPaymentInfo
     * - تتحول المقاعد من HELD إلى BOOKED
     * - يتم إلغاء reserved_until
     *
     * لا يتم إنشاء القيد المحاسبي هنا لأن الدفع
     * ما زال بانتظار مراجعة واعتماد الأدمن.
     */
    public function store(
        StoreUserDirectPaymentInfoRequest $request
    ): JsonResponse {
        $validated = $request->validated();

        try {
            DB::beginTransaction();

            /*
            |--------------------------------------------------------------------------
            | 1. جلب الحجز والتأكد أنه للمستخدم الحالي
            |--------------------------------------------------------------------------
            */

            $booking = Booking::query()
                ->where('uuid', $validated['booking_uuid'])
                ->where('user_id', auth()->id())
                ->lockForUpdate()
                ->first();

            if (! $booking) {
                DB::rollBack();

                return $this->error(
                    'الحجز غير موجود أو غير مصرح لك باستخدامه',
                    null,
                    404
                );
            }

            /*
            |--------------------------------------------------------------------------
            | 2. التحقق من حالة الحجز
            |--------------------------------------------------------------------------
            */

            if (
                (int) $booking->booking_status_id !==
                BookingStatus::PENDING
            ) {
                DB::rollBack();

                return $this->error(
                    'لا يمكن إرسال بيانات الدفع لهذا الحجز',
                    null,
                    422
                );
            }

            /*
            |--------------------------------------------------------------------------
            | 3. التحقق من صلاحية الـ Hold
            |--------------------------------------------------------------------------
            */

            $heldSeats = TripSeat::query()
                ->where('booking_id', $booking->id)
                ->where(
                    'trip_seat_status_id',
                    TripSeatStatus::HELD
                )
                ->whereNotNull('reserved_until')
                ->lockForUpdate()
                ->get();

            if ($heldSeats->isEmpty()) {
                DB::rollBack();

                return $this->error(
                    'لا توجد مقاعد مؤقتة مرتبطة بهذا الحجز',
                    null,
                    422
                );
            }

            /*
            |--------------------------------------------------------------------------
            | التحقق من أن الـ Hold لم ينته
            |--------------------------------------------------------------------------
            */

            $expiredSeat = $heldSeats->first(
                fn ($seat) => $seat->reserved_until?->isPast()
            );

            if ($expiredSeat) {
                DB::rollBack();

                return $this->error(
                    'انتهت مهلة الحجز المؤقت للمقاعد',
                    null,
                    422
                );
            }

            /*
            |--------------------------------------------------------------------------
            | 4. التحقق من حساب التحويل المباشر
            |--------------------------------------------------------------------------
            |
            | payment_method_id = 2
            |
            */

            $directTransferPayment = DirectTransferPayment::query()
                ->where(
                    'id',
                    $validated['direct_transfer_payment_id']
                )
                ->where(
                    'payment_method_id',
                    2
                )
                ->whereHas('paymentMethod')
                ->first();

            if (! $directTransferPayment) {
                DB::rollBack();

                return $this->error(
                    'بيانات التحويل المباشر غير صالحة',
                    null,
                    422
                );
            }

            /*
            |--------------------------------------------------------------------------
            | 5. منع وجود عملية دفع معلقة لنفس الحجز
            |--------------------------------------------------------------------------
            */

            $pendingPaymentExists = BookingPayment::query()
                ->where(
                    'booking_id',
                    $booking->id
                )
                ->where(
                    'payment_method_id',
                    $directTransferPayment->payment_method_id
                )
                ->where(
                    'paid_status',
                    'pending'
                )
                ->exists();

            if ($pendingPaymentExists) {
                DB::rollBack();

                return $this->error(
                    'يوجد طلب دفع معلق لهذا الحجز بالفعل',
                    null,
                    422
                );
            }

            /*
            |--------------------------------------------------------------------------
            | 6. إنشاء BookingPayment
            |--------------------------------------------------------------------------
            |
            | المستخدم لا يرسل booking_payment_id.
            | النظام ينشئه هنا ويحتفظ بالـ ID داخليًا.
            |
            */

            $bookingPayment = BookingPayment::create([
                'uuid' => Str::uuid()->toString(),

                'payment_method_id' =>
                    $directTransferPayment->payment_method_id,

                'user_id' =>
                    auth()->id(),

                'booking_id' =>
                    $booking->id,

                'payed_amount' =>
                    $booking->amount,

                'currency_id' =>
                    $booking->currency_id,

                'exchange_rate' =>
                    $booking->exchange_rate,

                'base_amount' =>
                    $booking->base_amount,

                'paid_status' =>
                    'pending',

                'note' =>
                    'تحويل مباشر - بانتظار مراجعة واعتماد الدفع',
            ]);

            /*
            |--------------------------------------------------------------------------
            | 7. إنشاء معلومات التحويل
            |--------------------------------------------------------------------------
            */

            $directPaymentInfo = UserDirectPaymentInfo::create([
                'uuid' =>
                    Str::uuid()->toString(),

                'direct_transfer_payment_id' =>
                    $directTransferPayment->id,

                'booking_payment_id' =>
                    $bookingPayment->id,

                'receipt_number' =>
                    $validated['receipt_number'] ?? null,

                'sender_name' =>
                    $validated['sender_name'],
            ]);

            /*
            |--------------------------------------------------------------------------
            | 8. تحويل المقاعد من HELD إلى BOOKED
            |--------------------------------------------------------------------------
            |
            | بمجرد إرسال بيانات التحويل:
            |
            | HELD -> BOOKED
            |
            | وبالتالي لا يعود الـ Timer مؤثرًا على المقاعد.
            |
            */

            foreach ($heldSeats as $seat) {
                $seat->update([
                    'trip_seat_status_id' =>
                        TripSeatStatus::BOOKED,

                    'reserved_until' =>
                        null,
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | 9. تثبيت العملية
            |--------------------------------------------------------------------------
            */

            DB::commit();

            /*
            |--------------------------------------------------------------------------
            | 10. تحميل العلاقات
            |--------------------------------------------------------------------------
            */

            $directPaymentInfo->load([
                'directTransferPayment',
                'bookingPayment',
            ]);

            /*
            |--------------------------------------------------------------------------
            | 11. Audit Log - Booking Payment
            |--------------------------------------------------------------------------
            */

            $this->auditService->log(
                auth()->id(),
                'create',
                'booking_payments',
                $bookingPayment->id,
                null,
                $bookingPayment->toArray()
            );

            /*
            |--------------------------------------------------------------------------
            | 12. Audit Log - Direct Payment Info
            |--------------------------------------------------------------------------
            */

            $this->auditService->log(
                auth()->id(),
                'create',
                'user_direct_payment_infos',
                $directPaymentInfo->id,
                null,
                $directPaymentInfo->toArray()
            );

            /*
            |--------------------------------------------------------------------------
            | 13. Response
            |--------------------------------------------------------------------------
            */

            return $this->success(
                new UserDirectPaymentInfoResource(
                    $directPaymentInfo
                ),
                'تم إرسال بيانات التحويل بنجاح، وتم حجز المقاعد بانتظار مراجعة الدفع',
                201
            );

        } catch (\Throwable $e) {

            /*
            |--------------------------------------------------------------------------
            | Rollback
            |--------------------------------------------------------------------------
            */

            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            Log::error(
                'Failed to create direct transfer payment',
                [
                    'user_id' =>
                        auth()->id(),

                    'booking_uuid' =>
                        $validated['booking_uuid'] ?? null,

                    'direct_transfer_payment_id' =>
                        $validated['direct_transfer_payment_id'] ?? null,

                    'error' =>
                        $e->getMessage(),

                    'trace' =>
                        $e->getTraceAsString(),
                ]
            );

            return $this->error(
                'حدث خطأ أثناء تسجيل بيانات التحويل المباشر',
                null,
                500
            );
        }
    }
}