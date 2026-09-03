<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Booking\BookTripRequest;
use App\Http\Requests\Api\Booking\BookRoundTripRequest;
use App\Http\Resources\BookingResource;
use App\Services\BookingService;
use App\Services\CommissionService;
use App\Services\BranchService;
use App\Services\RefundService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use App\Constants\PaymentMethod;
use App\DTOs\BookTripDTO;
class BookingController extends Controller
{
    use ApiResponse;

    protected BookingService $bookingService;
    protected CommissionService $commissionService;
    protected BranchService $branchService;
    protected RefundService $refundService;


    public function __construct(
        BookingService $bookingService,
        CommissionService $commissionService,
        BranchService $branchService,
        RefundService $refundService,
    ) {
        $this->bookingService = $bookingService;
        $this->middleware('auth:sanctum');
        $this->commissionService = $commissionService;
        $this->branchService = $branchService;
        $this->refundService = $refundService;
    }


    public function index(): JsonResponse
    {
        $bookings = $this->bookingService->getUserBookings(auth()->id());
        return $this->success(BookingResource::collection($bookings), 'تم استرجاع الحجوزات بنجاح');
    }


    public function show(string $uuid): JsonResponse
    {
        $booking = $this->bookingService->getBookingDetails($uuid, auth()->id());
        
        if (!$booking) {
            return $this->notFound('الحجز غير موجود');
        }

        return $this->success(new BookingResource($booking), "تم استرجاع تفاصيل الحجز بنجاح");
    }


    public function store(BookTripRequest $request, string $tripUuid): JsonResponse
    {
        $dto = BookTripDTO::fromRequest($request->validated(), auth()->id(), $tripUuid);
        $result = $this->bookingService->bookTrip($dto);

        if (!$result['success']) {
            return $this->error($result['message'], null, 400);
        }

        return $this->success($result['data'], $result['message'], 201);
    }


    public function bookRoundTrip(BookRoundTripRequest $request): JsonResponse
    {
        $data = $request->validated();

        $tripUuids = [
            $data['outbound_trip_uuid'],
            $data['return_trip_uuid']
        ];

        $departureDates = [];
        if (isset($data['outbound_travel_date'])) {
            $departureDates[] = $data['outbound_travel_date'];
        }
        if (isset($data['return_travel_date'])) {
            $departureDates[] = $data['return_travel_date'];
        }
        $data['departure_dates'] = $departureDates;

        $dto = BookTripDTO::fromRequest($data, auth()->id(), $tripUuids);
        $result = $this->bookingService->bookTrip($dto);

        if (!$result['success']) {
            return $this->error($result['message'], null, 400);
        }

        return $this->success($result['data'], 'تم إنشاء حجز الذهاب والعودة بنجاح');
    }


    public function cancel(string $uuid): JsonResponse
    {
        $booking = \App\Models\Booking::where('uuid', $uuid)
            ->where('user_id', auth()->id())
            ->first();

        if (!$booking) {
            return $this->notFound('الحجز غير موجود');
        }

        $result = $this->bookingService->cancelBooking($booking, auth()->id());

        if (!$result['success']) {
            return $this->error($result['message'], 400);
        }

        return $this->success([
            'booking_uuid' => $uuid,
            'refund_amount' => $result['data']['refund_amount'] ?? 0,
            'cancellation_fee' => $result['data']['cancellation_fee'] ?? 0,
        ], $result['message']);
    }

    public function seats(string $tripUuid): JsonResponse
    {
        $seats = $this->bookingService->getTripSeats($tripUuid);
        return $this->success($seats, 'تم استرجاع المقاعد بنجاح');
    }


    public function getCommissions(string $uuid): JsonResponse
    {
        if (!auth()->user()->isAdmin() && !auth()->user()->isProvider()) {
            return $this->error('غير مصرح لك بعرض تفاصيل العمولات', 403);
        }

        $booking = \App\Models\Booking::where('uuid', $uuid)
            ->with('commission')->first();

        if (!$booking) {
            return $this->notFound('الحجز غير موجود');
        }

        if (!auth()->user()->isAdmin() && auth()->user()->isProvider()) {
            $providerId = auth()->user()->providerService?->id;
            if ($booking->provider_id !== $providerId) {
                return $this->error('غير مصرح لك بعرض تفاصيل العمولات لهذا الحجز', 403);
            }
        }

        return $this->success([
            'commission_id' => $booking->commission_id,
            'commission_value' => $booking->commission_value,
            'commission_amount' => $booking->commission_amount,
            'commission' => $booking->commission,
        ], 'تم استرجاع تفاصيل العمولات بنجاح');
    }


    public function applyCommission(\Illuminate\Http\Request $request, string $uuid): JsonResponse
    {
        if (!auth()->user()->isAdmin()) {
            return $this->error('غير مصرح لك بتعديل العمولات', 403);
        }

        $data = $request->validate([
            'commission_id' => 'required|exists:commissions,id',
            'commission_value' => 'required|numeric|min:0',
            'commission_amount' => 'required|numeric|min:0',
        ]);

        $booking = \App\Models\Booking::where('uuid', $uuid)->first();
        
        if (!$booking) {
            return $this->notFound('الحجز غير موجود');
        }

        $booking->update([
            'commission_id' => $data['commission_id'],
            'commission_value' => $data['commission_value'],
            'commission_amount' => $data['commission_amount'],
        ]);

        return $this->success($booking->fresh(), 'تم تحديث العمولات بنجاح');
    }

    public function getBranch(string $uuid): JsonResponse
    {
        $booking = \App\Models\Booking::where('uuid', $uuid)
        ->where('user_id', auth()->id())
        ->with('branch')
        ->first();
        
        if (!$booking) {
            return $this->notFound('الحجز غير موجود');
        }

        return $this->success($booking->branch, 'تم استرجاع معلومات الفرع بنجاح');
    }

    public function confirmBoarding(string $ticketUuid): JsonResponse
    {
        $result = $this->bookingService->confirmBoarding($ticketUuid, auth()->id());

        if (!$result['success']) {
            return $this->error($result['message'], $result['code'] ?? 409);
        }

        return $this->success($result['data'], $result['message']);
    }

    public function myBoardedTickets(): JsonResponse
    {
        $result = $this->bookingService->getMyBoardedTickets(auth()->id());

        if (!$result['success']) {
            return $this->error($result['message'], $result['code'] ?? 400);
        }

        return $this->success($result['data'], 'تم جلب التذاكر التي قمت بتأكيدها بنجاح');
    }

    /**
     * مسك المقاعد مؤقتاً لمدة 5 دقائق قبل استكمال الحجز والدفع
     * تعديل: تمت الإضافة لتنفيذ طلبات Hold المقاعد من الجوال/الموقع
     */
    public function holdSeats(\Illuminate\Http\Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'seat_ids'   => 'required|array|min:1',
            'seat_ids.*' => 'integer|exists:seats,id',
        ]);

        $userId = auth()->id();
        $result = $this->bookingService->holdSeats($uuid, $request->input('seat_ids'), $userId, 2);

        if (!$result['success']) {
            return $this->error($result['message'] ?? 'فشل مسك المقاعد المؤقت', null, 422);
        }

        return $this->success($result, $result['message']);
    }
}

