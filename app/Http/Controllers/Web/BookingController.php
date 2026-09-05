<?php

namespace App\Http\Controllers\Web;

use App\Constants\TripStatus;
use App\Constants\UserTypes;
use App\DTOs\BookTripDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Booking\AdminIndexBookingRequest;
use App\Http\Requests\Web\Booking\BookRoundTripRequest;
use App\Http\Requests\Web\Booking\BookTripRequest;
use App\Http\Requests\Web\Booking\SearchReturnTripsRequest;
use App\Http\Resources\Admin\AdminBookingDetailsResource;
use App\Http\Resources\Admin\BookingDetailsResource;
use App\Http\Resources\Admin\BookingListResource;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\City;
use App\Models\Gender;
use App\Models\Trip;
use App\Models\User;
use App\Services\BookingService;
use App\Services\CurrencyService;
use App\Services\ReturnTripSearchService;
use App\Services\Wallet\WalletService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class BookingController extends Controller
{
    protected BookingService $bookingService;

    protected CurrencyService $currencyService;

    protected ReturnTripSearchService $returnTripSearch;

    public function __construct(
        BookingService $bookingService,
        CurrencyService $currencyService,
        ReturnTripSearchService $returnTripSearch,
    ) {
        $this->bookingService = $bookingService;
        $this->currencyService = $currencyService;
        $this->returnTripSearch = $returnTripSearch;
        $this->middleware('auth');
    }

    public function index(AdminIndexBookingRequest $request): JsonResponse
    {
        $user = auth()->user();

        if ($user->isAdmin()) {
            $bookings = $this->bookingService->getAllBookings(
                perPage: $request->validated('per_page') ?? 15,
                statusFilter: $request->validated('status'),
                search: $request->validated('search'),
            );

            $data = BookingListResource::collection($bookings)
                ->response()
                ->getData(true);
        } else {
            $bookings = $this->bookingService->getUserBookings($user->id);

            $data = BookingResource::collection($bookings)
                ->response()
                ->getData(true);
        }

        return $this->success(
            $data,
            'تم جلب الحجوزات بنجاح'
        );
    }

    /*
    public function index(AdminIndexBookingRequest $request): View
    {
        $user = auth()->user();
        $baseCurrency = $this->currencyService->baseCurrency();

        if ($user->isAdmin()) {
            $bookings = $this->bookingService->getAllBookings(
                perPage: $request->validated('per_page') ?? 15,
                statusFilter: $request->validated('status'),
                search: $request->validated('search'),
            );
        } else {
            $bookings = $this->bookingService->getUserBookings($user->id);
        }

        $view = $user->isAdmin() ? 'admin.bookings.index' : 'bookings.index';

        return view($view, compact('bookings', 'baseCurrency'));
    }
    */

    public function bookTrip(string $uuid): View
    {
        $trip = Trip::with([
            'departureCity', 'destinationCity', 'vehicle.seats', 'vehicle.vehicleType',
            'amenities', 'departureStation', 'arrivalStation',
            'levelTrip', 'typeOfTrip', 'user.providerService',
        ])
            ->where('uuid', $uuid)
            ->where('is_public', true)
            ->whereIn('trip_status_id', [TripStatus::SCHEDULED, TripStatus::IN_PROGRESS])
            ->first();

        if (! $trip) {
            abort(404, 'الرحلة غير موجودة أو غير متاحة للحجز');
        }

        if ($trip->departure_time <= now()) {
            abort(404, 'انتهت صلاحية هذه الرحلة');
        }

        if ($trip->available_seats <= 0) {
            abort(404, 'لا توجد مقاعد متاحة في هذه الرحلة');
        }

        $baseCurrencyId = $this->currencyService->baseCurrencyId();
        $baseCurrency = $this->currencyService->baseCurrency();
        $currencies = $this->currencyService->getAllCurrenciesWithRates();

        $genders = Cache::remember('genders-list', 3600, function () {
            return Gender::select('id', 'name')->orderBy('name')->get();
        });

        $previousPassengers = $this->bookingService->getUserPreviousPassengers(auth()->id());

        $walletBalance = 0;
        $walletService = app(WalletService::class);
        if ($walletService->isWalletEnabled() && $walletService->isWalletPaymentEnabled()) {
            $walletAccount = $walletService->getOrCreateAccount(auth()->id(), $baseCurrencyId);
            $walletBalance = (float) $walletAccount->balance;
        }

        return view('home.booking', compact(
            'trip', 'currencies', 'baseCurrencyId', 'baseCurrency',
            'genders', 'previousPassengers', 'walletBalance',
        ));
    }

    public function create(): View
    {
        $trips = Trip::with(['tripType', 'city', 'vehicleType'])
            ->whereHas('tripStatus', function ($query) {
                $query->where('status_name', 'active');
            })
            ->where('departure_time', '>=', now())
            ->orderBy('departure_time')
            ->limit(50)
            ->get();

        $cities = cache()->remember('cities-list', 3600, function () {
            return City::orderBy('name')->get(['id', 'name']);
        });

        $users = auth()->user()->isAdmin()
            ? User::where('type_user_code', UserTypes::CUSTOMER)
                ->orderBy('name')
                ->limit(100)
                ->get(['id', 'name'])
            : collect();

        $previousPassengers = auth()->check()
            ? $this->bookingService->getUserPreviousPassengers(auth()->id())
            : collect();

        $view = auth()->user()->isAdmin() ? 'admin.bookings.create' : 'bookings.create';

        return view($view, compact('trips', 'cities', 'users', 'previousPassengers'));
    }

    public function show(string $uuid): JsonResponse
    {
        $relations = [
            'status:id,status_name',
            'payment:id,booking_id,payment_method_id,payed_amount,currency_id,exchange_rate',
            'payment.paymentMethod:id,method_name',
            'payment.currency:id,code,name',
            'currency:id,code,name',
            'user:id,name,email,phone_number',
            'tripReservations.trip.departureCity:id,name',
            'tripReservations.trip.destinationCity:id,name',
            'tripReservations.trip.vehicle:id,name,model',
            'tripReservations.trip.vehicle.vehicleType:id,type_name',
            'tripReservations.trip.departureStation:id,name',
            'tripReservations.trip.arrivalStation:id,name',
            'tripReservations.trip.user:id,name',
            'tripReservations.trip.user.providerService:id,name,brand_photo',
            'tripReservations.tickets.passenger:id,full_name,birthday,gender_id,phone_number',
            'tripReservations.tickets.seat:id,seat_name',
            'taxes',
            'couponUsages.coupon:id,code,discount_type,discount_value',
        ];

        $booking = Booking::with($relations)
            ->where('uuid', $uuid)
            ->first();

        if (! $booking) {
            return $this->error('الحجز غير موجود', null, 404);
        }

        if ($booking->user_id !== Auth::id() && ! Auth::user()->isAdmin()) {
            return $this->error(
                'غير مصرح لك بعرض هذا الحجز',
                null,
                403
            );
        }

        return $this->success(
            new BookingDetailsResource($booking),
            'تم جلب تفاصيل الحجز بنجاح'
        );
    }
    /*
        public function show(string $uuid): View
        {
            $relations = [
                'status:id,status_name',
                'payment:id,booking_id,payment_method_id,payed_amount,currency_id,exchange_rate',
                'payment.paymentMethod:id,method_name',
                'payment.currency:id,code,name',
                'currency:id,code,name',
                'user:id,name,email,phone_number',
                'tripReservations.trip.departureCity:id,name',
                'tripReservations.trip.destinationCity:id,name',
                'tripReservations.trip.vehicle:id,name,model',
                'tripReservations.trip.vehicle.vehicleType:id,type_name',
                'tripReservations.trip.departureStation:id,name',
                'tripReservations.trip.arrivalStation:id,name',
                'tripReservations.trip.user:id,name',
                'tripReservations.trip.user.providerService:id,name,brand_photo',
                'tripReservations.tickets.passenger:id,full_name,birthday,gender_id,phone_number',
                'tripReservations.tickets.seat:id,seat_name',
                'taxes',
                'couponUsages.coupon:id,code,discount_type,discount_value',
            ];

            $booking = Booking::with($relations)->where('uuid', $uuid)->first();

            if (! $booking) {
                abort(404, 'الحجز غير موجود');
            }

            if ($booking->user_id !== Auth::id() && ! Auth::user()->isAdmin()) {
                abort(403, 'غير مصرح لك بعرض هذا الحجز');
            }

            $baseCurrency = $this->currencyService->baseCurrency();

            return view('bookings.show', compact('booking', 'baseCurrency'));
        }
            */

    public function print(string $uuid): View
    {
        $relations = [
            'tripReservations.trip.departureCity:id,name',
            'tripReservations.trip.destinationCity:id,name',
            'tripReservations.trip.vehicle:id,name,model',
            'tripReservations.trip.vehicle.vehicleType:id,type_name',
            'tripReservations.trip.departureStation:id,name',
            'tripReservations.trip.arrivalStation:id,name',
            'tripReservations.trip.user:id,name',
            'tripReservations.trip.user.providerService:id,name,brand_photo',
            'tripReservations.tickets.passenger:id,full_name,birthday,gender_id,phone_number',
            'tripReservations.tickets.seat:id,seat_name',
            'currency:id,code,name',
        ];

        $booking = Booking::with($relations)->where('uuid', $uuid)->first();

        if (! $booking) {
            abort(404);
        }

        if ($booking->user_id !== Auth::id() && ! Auth::user()->isAdmin()) {
            abort(403);
        }

        $baseCurrency = $this->currencyService->baseCurrency();

        return view('bookings.print', compact('booking', 'baseCurrency'));
    }

    public function store(BookTripRequest $request, string $tripUuid): RedirectResponse
    {
        $data = $request->validated();
        $dto = BookTripDTO::fromRequest($data, Auth::id(), $tripUuid);
        $result = $this->bookingService->bookTrip($dto);

        if ($result['success']) {
            $bookingUuid = $result['data']['booking_uuid'] ?? '';

            return redirect()->route('bookings.show', $bookingUuid)->with('success', $result['message']);
        }

        return redirect()->back()->withInput()->with('error', $result['message']);
    }

    public function createRoundTrip(Trip $trip): View
    {
        $trip = Trip::with([
            'departureCity', 'destinationCity', 'vehicle.seats', 'vehicle.vehicleType',
            'amenities', 'departureStation', 'arrivalStation',
            'levelTrip', 'typeOfTrip', 'user.providerService',
        ])->where('id', $trip->id)
            ->where('is_public', true)
            ->whereIn('trip_status_id', [TripStatus::SCHEDULED, TripStatus::IN_PROGRESS])
            ->first();

        if (! $trip || $trip->departure_time <= now() || $trip->available_seats <= 0) {
            abort(404, 'الرحلة غير متاحة للحجز');
        }

        $arrivalDay = Carbon::parse($trip->arrival_time)->startOfDay();
        $defaultReturnDate = (now()->startOfDay()->gt($arrivalDay))
            ? now()->startOfDay()
            : $arrivalDay;

        $returnTrips = $this->getReturnTripsForDate($trip, $defaultReturnDate);

        $baseCurrencyId = $this->currencyService->baseCurrencyId();
        $baseCurrency = $this->currencyService->baseCurrency();
        $currencies = $this->currencyService->getAllCurrenciesWithRates();

        $genders = Cache::remember('genders-list', 3600, function () {
            return Gender::select('id', 'name')->orderBy('name')->get();
        });

        $previousPassengers = $this->bookingService->getUserPreviousPassengers(auth()->id());

        $walletBalance = 0;
        $walletService = app(WalletService::class);
        if ($walletService->isWalletEnabled() && $walletService->isWalletPaymentEnabled()) {
            $walletAccount = $walletService->getOrCreateAccount(auth()->id(), $baseCurrencyId);
            $walletBalance = (float) $walletAccount->balance;
        }

        $isRoundTrip = true;

        return view('home.booking', compact(
            'trip', 'currencies', 'baseCurrencyId', 'baseCurrency',
            'genders', 'previousPassengers', 'walletBalance',
            'isRoundTrip', 'returnTrips', 'defaultReturnDate',
        ));
    }

    protected function getReturnTripsForDate(Trip $outboundTrip, Carbon $targetDate): Collection
    {
        return $this->returnTripSearch->search($outboundTrip, $targetDate);
    }

    public function searchReturnTrips(SearchReturnTripsRequest $request): JsonResponse
    {
        $outboundTrip = $request->getOutboundTrip();
        $targetDate = $request->getTargetDate();

        if (! $outboundTrip) {
            return response()->json(['success' => false, 'message' => 'رحلة الذهاب غير موجودة'], 404);
        }

        $returnTrips = $this->returnTripSearch->search($outboundTrip, $targetDate);

        $formatted = $returnTrips->map(function ($rt) use ($outboundTrip) {
            $hasDiscount = ($rt->discount_percentage ?? 0) > 0;
            $priceAdult = $hasDiscount
                ? $rt->price_for_adult * (1 - $rt->discount_percentage / 100)
                : $rt->price_for_adult;
            $priceChild = $hasDiscount
                ? $rt->price_for_child * (1 - $rt->discount_percentage / 100)
                : $rt->price_for_child;

            $departure = Carbon::parse($rt->departure_time);
            $arrival = Carbon::parse($rt->arrival_time);

            return [
                'uuid' => $rt->uuid,
                'departure_time' => $departure->format('h:i A'),
                'departure_date' => $departure->format('d/m/Y'),
                'arrival_time' => $arrival->format('h:i A'),
                'price_adult' => (float) $priceAdult,
                'price_child' => (float) $priceChild,
                'available_seats' => $rt->available_seats,
                'vehicle_type' => $rt->vehicle && $rt->vehicle->vehicleType
                    ? $rt->vehicle->vehicleType->type_name : '',
                'provider_name' => $rt->user && $rt->user->providerService
                    ? $rt->user->providerService->name
                    : ($rt->user ? $rt->user->name : ''),
                'is_same_provider' => $rt->user_id === $outboundTrip->user_id,
                'is_recurring' => $rt->parent_id !== null,
                'discount' => $hasDiscount ? (float) $rt->discount_percentage : 0,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'trips' => $formatted,
                'count' => $formatted->count(),
                'date' => $targetDate->toDateString(),
                'min_date' => $this->returnTripSearch->minSearchDate($outboundTrip),
            ],
        ]);
    }

    public function bookRoundTrip(BookRoundTripRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $tripUuids = [$data['outbound_trip_uuid'], $data['return_trip_uuid']];
        $departureDates = [
            $data['outbound_travel_date'] ?? null,
            $data['return_travel_date'] ?? null,
        ];
        $data['departure_dates'] = $departureDates;
        $dto = BookTripDTO::fromRequest($data, Auth::id(), $tripUuids);
        $result = $this->bookingService->bookTrip($dto);

        if ($result['success']) {
            $bookingUuid = $result['data']['booking_uuid'] ?? '';

            return redirect()->route('bookings.show', $bookingUuid)->with('success', 'تم إنشاء الحجز ذهاب وعودة بنجاح');
        }

        return redirect()->back()->withInput()->with('error', $result['message']);
    }

    public function cancel(string $uuid): JsonResponse
    {
        $booking = Booking::where('uuid', $uuid)->first();

        if (! $booking) {
            return $this->error('الحجز غير موجود', null, 404);
        }

        $user = Auth::user();

        // تحقق الصلاحية مع إغلاق القوس بشكل صحيح }
        if ($booking->user_id !== $user->id && ! $user->isAdmin()) {
            return $this->error('غير مصرح لك بإلغاء هذا الحجز', null, 403);
        } // 👈 هذا القوس كان مفقوداً عندكِ

        $result = $this->bookingService->cancelBooking(
            $booking,
            $user->id
        );

        if ($result['success']) {
            return $this->success(
                [
                    'booking' => [
                        'uuid' => $booking->uuid,
                        'status' => $booking->fresh()->status,
                    ],
                ],
                $result['message']
            );
        }

        return $this->error(
            $result['message'],
            null,
            422
        );
    }
    /*

    public function cancel(string $uuid): RedirectResponse
    {
        $booking = Booking::where('uuid', $uuid)->first();

        if (! $booking) {
            abort(404, 'الحجز غير موجود');
        }

        if ($booking->user_id !== Auth::id() && ! Auth::user()->isAdmin()) {
            abort(403, 'غير مصرح لك بإلغاء هذا الحجز');
        }

        $result = $this->bookingService->cancelBooking($booking, Auth::id());
        if ($result['success']) {
            return redirect()->route('bookings.show', $booking->uuid)
                ->with('success', $result['message']);
        }

        return redirect()->back()->with('error', $result['message']);
    }
    */

    public function seats(string $tripUuid): View
    {
        $seats = $this->bookingService->getTripSeats($tripUuid);

        return view('bookings.seats', compact('seats', 'tripUuid'));
    }
}
