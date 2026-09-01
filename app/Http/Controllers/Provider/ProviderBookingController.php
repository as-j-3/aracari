<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Provider\StoreProviderBookingRequest;
use App\Models\Booking;
use App\Models\ProviderBranch;
use App\Models\Trip;
use App\Services\BookingService;
use App\Services\AuditService;
use App\Services\RefundService;
use App\Services\ProviderContext;
use App\DTOs\BookTripDTO;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Constants\BookingStatus;
use Illuminate\Support\Facades\Log;

class ProviderBookingController extends Controller
{
    protected BookingService $bookingService;
    protected AuditService $auditService;
    protected RefundService $refundService;
    protected ProviderContext $providerContext;

    public function __construct(
        BookingService $bookingService,
        AuditService $auditService,
        RefundService $refundService,
        ProviderContext $providerContext
    ) {
        $this->bookingService = $bookingService;
        $this->auditService = $auditService;
        $this->refundService = $refundService;
        $this->providerContext = $providerContext;
    }


    public function allIndex(Request $request): View
    {
        $user = $request->user();
        $providerId = $this->providerContext->providerUserIdFor($user);

        $branches = ProviderBranch::where('provider_id', function ($q) use ($providerId) {
            $q->select('id')->from('provider_services')->where('user_id', $providerId);
        })->where('status', 'active')->get();

        $query = Booking::whereHas('tripReservations.trip.service.branch', function ($q) use ($providerId) {
            $q->where('provider_id', function ($pq) use ($providerId) {
                $pq->select('id')->from('provider_services')->where('user_id', $providerId);
            });
        })->with(['user', 'status', 'tripReservations.trip.departureCity', 'tripReservations.trip.destinationCity', 'tripReservations.trip.service.branch']);

        $selectedBranch = $request->get('branch');
        if ($selectedBranch) {
            $query->whereHas('tripReservations.trip.service', fn($q) => $q->where('branch_id', $selectedBranch));
        }

        $bookings = $query->latest()->paginate(20)->withQueryString();

        return view('provider.bookings.index', compact('bookings', 'branches', 'selectedBranch'));
    }

    public function index(ProviderBranch $branch): View
    {
        $this->authorize('access', $branch);

        $bookings = Booking::forBranch($branch->id)
            ->with(['user', 'status', 'currency', 'tripReservations.trip.departureCity', 'tripReservations.trip.destinationCity'])
            ->latest()
            ->paginate(20);

        return view('provider.branches.bookings.index', compact('bookings', 'branch'));
    }

    public function store(StoreProviderBookingRequest $request, ProviderBranch $branch): RedirectResponse
    {
        $this->authorize('access', $branch);

        $user = auth()->user();

        try {
            $trip = Trip::where('uuid', $request->trip_uuid)->first();

            if (!$trip) {
                return redirect()->back()->withInput()
                    ->with('error', 'الرحلة المحددة غير موجودة');
            }

            $providerOwnerId = $user->user_parent ?? $user->id;
            if (!$trip->vehicle || !in_array($trip->vehicle->user_id, [$user->id, $user->user_parent])) {
                Log::warning('Provider booking attempt on foreign trip', [
                    'user_id' => $user->id,
                    'trip_uuid' => $request->trip_uuid,
                    'vehicle_owner' => $trip->vehicle?->user_id,
                    'provider_owner' => $providerOwnerId,
                ]);
                return redirect()->back()->withInput()
                    ->with('error', 'الرحلة المحددة لا تنتمي لمزودك');
            }

            $departureDates = [];
            if ($request->outbound_travel_date) $departureDates[] = $request->outbound_travel_date;
            if ($request->return_travel_date) $departureDates[] = $request->return_travel_date;

            $dto = new BookTripDTO(
                userId: $user->id,
                tripUuids: [$request->trip_uuid],
                adults: $request->adults,
                children: $request->children,
                paymentMethodId: $request->payment_method_id,
                passengersData: $request->passenger_details,
                couponCode: $request->coupon_code,
                departureDates: $departureDates,
                paymentCurrencyId: (int) ($request->currency_id ?? 0),
            );

            $result = $this->bookingService->bookTrip($dto);

            if (!$result['success']) {
                return redirect()->back()->withInput()->with('error', $result['message']);
            }

            $bookingId = $result['data']['booking']['id'] ?? null;
            $this->auditService->log(
                $user->id,
                'create',
                'bookings',
                $bookingId,
                null,
                [
                    'booking_id' => $bookingId,
                    'booking_uuid' => $result['data']['booking']['uuid'] ?? null,
                    'trip_uuid' => $request->trip_uuid,
                    'branch_id' => $branch->id,
                    'adults' => $request->adults,
                    'children' => $request->children,
                    'total_amount' => $result['data']['total_amount'] ?? null,
                    'type' => 'provider_branch_booking',
                ]
            );

            return redirect()->route('provider.branches.bookings.index', $branch)
                ->with('success', 'تم إنشاء الحجز بنجاح');
        } catch (\Exception $e) {
            Log::error('Provider booking store failed: ' . $e->getMessage());
            return back()->withInput()
                ->with('error', 'حدث خطأ أثناء إنشاء الحجز. يرجى المحاولة مرة أخرى.');
        }
    }

    public function show(ProviderBranch $branch, Booking $booking)
    {
        $this->authorize('access', $branch);

        if ($booking->branch_id !== $branch->id) {
            return redirect()->route('provider.branches.bookings.index', $branch)
                ->with('error', 'الحجز غير موجود أو لا ينتمي لهذا الفرع');
        }

        $booking->load([
            'user', 'status', 'payment.paymentMethod', 'currency',
            'tripReservations.trip.departureCity', 'tripReservations.trip.destinationCity',
            'tripReservations.trip.vehicle.vehicleType', 'tripReservations.trip.departureStation', 'tripReservations.trip.arrivalStation',
            'tripReservations.trip.user.providerService',
            'tripReservations.tickets.passenger',
            'tripReservations.tickets.seat', 'taxes',
            'couponUsages.coupon',
        ]);

        return view('provider.branches.bookings.show', compact('booking', 'branch'));
    }


    public function cancel(ProviderBranch $branch, Booking $booking): RedirectResponse
    {
        $this->authorize('access', $branch);

        if ($booking->branch_id !== $branch->id) {
            return redirect()->route('provider.branches.bookings.index', $branch)
                ->with('error', 'الحجز غير موجود أو لا ينتمي لهذا الفرع');
        }

        $user = auth()->user();

        if ($booking->booking_status_id == BookingStatus::CANCELLED) {
            return redirect()->back()->with('error', 'الحجز ملغي بالفعل');
        }

        try {
            $result = $this->refundService->cancelBooking($booking, $user, [
                'branch_id' => $branch->id,
                'type' => 'provider_branch_booking_cancel',
            ]);

            if (!$result['success']) {
                return redirect()->back()->with('error', $result['message'] ?? 'فشل إلغاء الحجز');
            }

            return redirect()->route('provider.branches.bookings.index', $branch)
                ->with('success', 'تم إلغاء الحجز بنجاح');
        } catch (\Exception $e) {
            Log::error('Provider booking cancel failed: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء إلغاء الحجز. يرجى المحاولة مرة أخرى.');
        }
    }
}