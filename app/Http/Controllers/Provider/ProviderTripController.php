<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\Provider\StoreTripRequest;
use App\Http\Requests\Provider\UpdateTripRequest;
use App\Models\Amenity;
use App\Models\City;
use App\Models\CityStation;
use App\Models\Country;
use App\Models\Coupon;
use App\Models\LevelTrip;
use App\Models\Policy;
use App\Models\ProviderBranch;
use App\Models\Service;
use App\Models\Trip;
use App\Models\TripStatus;
use App\Models\TypeOfTrip;
use App\Models\Vehicle;
use App\Services\TripService;
use App\Services\ProviderContext;
use App\Constants\WeekDays;
use App\Constants\CouponStatus as CouponStatusConst;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;

class ProviderTripController extends Controller
{
    protected TripService $tripService;
    protected ProviderContext $providerContext;

    public function __construct(TripService $tripService, ProviderContext $providerContext)
    {
        $this->tripService = $tripService;
        $this->providerContext = $providerContext;
    }


    public function allIndex(Request $request): View
    {
        $user = $request->user();
        $providerId = $this->providerContext->providerUserIdFor($user);

        $branches = ProviderBranch::where('provider_id', function ($q) use ($providerId) {
            $q->select('id')->from('provider_services')->where('user_id', $providerId);
        })->where('status', 'active')->get();

        $query = Trip::whereHas('service', function ($q) use ($providerId) {
            $q->whereHas('branch', function ($bq) use ($providerId) {
                $bq->where('provider_id', function ($pq) use ($providerId) {
                    $pq->select('id')->from('provider_services')->where('user_id', $providerId);
                });
            });
        })->with(['departureCity', 'destinationCity', 'vehicle.vehicleType', 'service.branch', 'tripStatus', 'typeOfTrip', 'levelTrip', 'amenities', 'coupons', 'departureStation', 'arrivalStation']);

        $selectedBranch = $request->get('branch');
        if ($selectedBranch) {
            $query->whereHas('service', fn($q) => $q->where('branch_id', $selectedBranch));
        }

        $trips = $query->latest('departure_time')->paginate(20)->withQueryString();

        $stats = [
            'total'    => Trip::whereHas('service.branch', fn($q) => $q->where('provider_id', function ($pq) use ($providerId) {
                $pq->select('id')->from('provider_services')->where('user_id', $providerId);
            }))->count(),
            'upcoming' => Trip::whereHas('service.branch', fn($q) => $q->where('provider_id', function ($pq) use ($providerId) {
                $pq->select('id')->from('provider_services')->where('user_id', $providerId);
            }))->where('departure_time', '>', now())->count(),
            'completed' => Trip::whereHas('service.branch', fn($q) => $q->where('provider_id', function ($pq) use ($providerId) {
                $pq->select('id')->from('provider_services')->where('user_id', $providerId);
            }))->where('departure_time', '<=', now())->count(),
        ];

        $countries    = Country::orderBy('name')->get();
        $cities       = City::with('country')->orderBy('name')->get();
        $vehicles     = Vehicle::where('user_id', $user->id)->where('status', 'active')->with('vehicleType')->get();
        $typeOfTrips  = TypeOfTrip::orderBy('type_name')->get();
        $tripStatuses = TripStatus::orderBy('status_name')->get();
        $levelTrips   = LevelTrip::orderBy('level_name')->get();
        $amenities    = Amenity::orderBy('name')->get();
        $policies     = Policy::orderBy('name')->get();
        $coupons      = Coupon::where('user_id', $user->id)->where('status_id', CouponStatusConst::ACTIVE)->get();
        $weekDays     = array_combine(WeekDays::databaseValues(), array_values(WeekDays::all()));
        $cityStations = CityStation::active()->orderBy('city_id')->orderBy('name')->get();

        return view('provider.trips.index', compact(
            'trips', 'branches', 'selectedBranch', 'stats',
            'countries', 'cities', 'vehicles', 'typeOfTrips', 'tripStatuses',
            'levelTrips', 'amenities', 'policies', 'coupons', 'weekDays', 'cityStations'
        ));
    }

    public function index(ProviderBranch $branch): View
    {
        $this->authorize('access', $branch);

        $trips = Trip::whereHas('service', fn($q) => $q->where('branch_id', $branch->id))
            ->with(['departureCity', 'destinationCity', 'vehicle', 'service'])
            ->latest('departure_time')
            ->paginate(20);

        return view('provider.branches.trips.index', compact('trips', 'branch'));
    }

    public function create(ProviderBranch $branch): View
    {
        $this->authorize('access', $branch);

        $user = auth()->user();

        $cities = City::orderBy('name')->get();
        $vehicles = Vehicle::where('user_id', $user->id)
            ->where('status', 'active')
            ->with('vehicleType')
            ->get();
        $typeOfTrips = TypeOfTrip::orderBy('type_name')->get();
        $tripStatuses = TripStatus::orderBy('status_name')->get();
        $levelTrips = LevelTrip::orderBy('level_name')->get();
        $amenities = Amenity::orderBy('name')->get();
        $policies = Policy::orderBy('name')->get();
        $coupons = Coupon::where('user_id', $user->id)
            ->where('status_id', CouponStatusConst::ACTIVE)
            ->get();
        $cityStations = CityStation::active()->orderBy('city_id')->orderBy('name')->get();

        $weekDays = array_combine(
            WeekDays::databaseValues(),
            array_values(WeekDays::all())
        );

        return view('provider.branches.trips.create', compact(
            'branch', 'cities', 'vehicles', 'typeOfTrips',
            'tripStatuses', 'levelTrips', 'amenities', 'policies', 'coupons',
            'weekDays', 'cityStations'
        ));
    }


    public function store(StoreTripRequest $request, ProviderBranch $branch): RedirectResponse|JsonResponse
    {
        $this->authorize('access', $branch);

        $user = auth()->user();

        try {
            $service = Service::where('branch_id', $branch->id)->first();
            if (!$service) {
                return redirect()->back()->with('error', 'لا يوجد خدمة مرتبطة بهذا الفرع');
            }

            $validated = $request->validated();

            $waypoints = [];
            if (!empty($validated['waypoint_cities']) && is_array($validated['waypoint_cities'])) {
                foreach ($validated['waypoint_cities'] as $cityUuid) {
                    $waypoints[] = ['city_uuid' => $cityUuid];
                }
            }

            if (!empty($validated['vehicle_id'])) {
                $vehicle = Vehicle::where('uuid', $validated['vehicle_id'])->first();
                if (!$vehicle) {
                    return redirect()->back()->withInput()->with('error', 'المركبة المحددة غير موجودة');
                }

                $providerOwnerId = $user->user_parent ?? $user->id;
                if (!in_array($vehicle->user_id, [$user->id, $user->user_parent])) {
                    Log::warning('Provider trip creation with foreign vehicle', [
                        'user_id' => $user->id,
                        'vehicle_uuid' => $validated['vehicle_id'],
                        'vehicle_owner' => $vehicle->user_id,
                        'provider_owner' => $providerOwnerId,
                    ]);
                    return redirect()->back()->withInput()->with('error', 'المركبة المحددة لا تنتمي لمزودك');
                }
            }

            $repeatDays = $validated['days'] ?? $validated['repeat_days'] ?? [];
            $repeatConfig = null;
            if ($request->boolean('is_recurring') && !empty($repeatDays)) {
                $repeatConfig = [
                    'start_date' => $request->input('departure_time') ?? now(),
                    'end_date'   => $validated['end_date_repeat'] ?? null,
                    'days'       => $repeatDays,
                    'status'     => 'active'
                ];
            }

            $pickupStation = CityStation::where('uuid', $validated['pickup_location_id'] ?? '')->first();

            $tripData = [
                'service_id'          => $service->id,
                'currency_id'         => app(\App\Services\CurrencyService::class)->baseCurrencyId(),
                'departure_city'      => $validated['from_city_id'],
                'destination_city'    => $validated['to_city_id'],
                'departure_time'      => $request->input('departure_time'),
                'arrival_time'        => $request->input('arrival_time'),
                'check_in_time'       => $request->input('check_in_time'),
                'vehicle_id'          => $validated['vehicle_id'],
                'type_of_trip_id'     => $validated['type_of_trip_id'],
                'trip_status_id'      => $validated['trip_status_id'] ?? 1,
                'level_trip_id'       => $validated['level_trip_id'],
                'price_for_adult'     => $validated['price_for_adult'],
                'price_for_child'     => $validated['price_for_child'],
                'discount_percentage' => $validated['discount_percentage'] ?? 0,
                'pickup_location'     => $pickupStation?->name ?? '',
                'pickup_location_id'  => $validated['pickup_location_id'],
                'arrival_location_id' => $validated['arrival_location_id'],
                'conditions'          => $validated['conditions'] ?? null,
                'is_public'           => $request->boolean('is_public', true),
                'amenities'           => $validated['amenities'] ?? [],
                'policy_id'           => $validated['policy_id'] ?? null,
                'coupons'             => $validated['coupons'] ?? [],
                'waypoints'           => $waypoints,
                'repeat_config'       => $repeatConfig,
            ];

            $result = $this->tripService->createTrip($tripData, $user->id);

            if (!$result['success']) {
                return redirect()->back()->withInput()->with('error', $result['message']);
            }

            $redirectUrl = route('provider.trips.index', ['branch' => $branch->id]);
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'تم إنشاء الرحلة بنجاح', 'redirect' => $redirectUrl]);
            }
            return redirect()->to($redirectUrl)->with('success', 'تم إنشاء الرحلة بنجاح');
        } catch (\Exception $e) {
            Log::error('Provider trip store failed: ' . $e->getMessage());
            return back()->withInput()->with('error', 'حدث خطأ أثناء إنشاء الرحلة. يرجى المحاولة مرة أخرى.');
        }
    }

    public function edit(ProviderBranch $branch, Trip $trip)
    {
        $this->authorize('access', $branch);

        if (!$trip->service || $trip->service->branch_id !== $branch->id) {
            return redirect()->route('provider.branches.trips.index', $branch)
                ->with('error', 'الرحلة غير موجودة أو لا تنتمي لهذا الفرع');
        }

        $trip->load(['departureCity', 'destinationCity', 'vehicle', 'service', 'amenities', 'coupons', 'tripStatus']);

        $user = auth()->user();
        $cities = City::orderBy('name')->get();
        $vehicles = Vehicle::where('user_id', $user->id)->where('status', 'active')->with('vehicleType')->get();
        $typeOfTrips = TypeOfTrip::orderBy('type_name')->get();
        $tripStatuses = TripStatus::orderBy('status_name')->get();
        $levelTrips = LevelTrip::orderBy('level_name')->get();
        $amenities = Amenity::orderBy('name')->get();
        $policies = Policy::orderBy('name')->get();
        $coupons = Coupon::where('user_id', $user->id)
            ->where('status_id', CouponStatusConst::ACTIVE)
            ->get();

        $weekDays = array_combine(WeekDays::databaseValues(), array_values(WeekDays::all()));
        $tripRepeat = $trip->repeats()->first();
        $cityStations = CityStation::active()->orderBy('city_id')->orderBy('name')->get();

        return view('provider.branches.trips.edit', compact(
            'trip', 'branch', 'cities', 'vehicles', 'typeOfTrips',
            'tripStatuses', 'levelTrips', 'amenities', 'policies', 'coupons', 'weekDays', 'tripRepeat', 'cityStations'
        ));
    }


    public function update(UpdateTripRequest $request, ProviderBranch $branch, Trip $trip): RedirectResponse|JsonResponse
    {
        $this->authorize('access', $branch);

        if (!$trip->service || $trip->service->branch_id !== $branch->id) {
            return redirect()->route('provider.branches.trips.index', $branch)
                ->with('error', 'الرحلة غير موجودة أو لا تنتمي لهذا الفرع');
        }

        $user = auth()->user();

        if ($trip->departure_time <= now()) {
            return redirect()->back()->with('error', 'لا يمكن تعديل رحلة انطلقت بالفعل');
        }

        try {
            $validated = $request->validated();

            $waypoints = [];
            if (!empty($validated['waypoint_cities']) && is_array($validated['waypoint_cities'])) {
                foreach ($validated['waypoint_cities'] as $cityUuid) {
                    $waypoints[] = ['city_uuid' => $cityUuid];
                }
            }

            if (!empty($validated['vehicle_id'])) {
                $vehicle = Vehicle::where('uuid', $validated['vehicle_id'])->first();
                if (!$vehicle) {
                    return redirect()->back()->withInput()->with('error', 'المركبة المحددة غير موجودة');
                }
                if (!in_array($vehicle->user_id, [$user->id, $user->user_parent])) {
                    return redirect()->back()->withInput()->with('error', 'المركبة المحددة لا تنتمي لمزودك');
                }
            }

            $tripData = [
                'departure_city'     => $validated['from_city_id'] ?? null,
                'destination_city'   => $validated['to_city_id'] ?? null,
                'departure_time'     => $request->input('departure_time'),
                'arrival_time'       => $request->input('arrival_time'),
                'check_in_time'      => $request->input('check_in_time'),
                'vehicle_id'         => $validated['vehicle_id'] ?? null,
                'type_of_trip_id'    => $validated['type_of_trip_id'] ?? null,
                'level_trip_id'      => $validated['level_trip_id'] ?? null,
                'trip_status_id'     => $validated['trip_status_id'] ?? null,
                'pickup_location_id'  => $validated['pickup_location_id'] ?? null,
                'arrival_location_id' => $validated['arrival_location_id'] ?? null,
                'price_for_adult'    => $validated['price_for_adult'] ?? null,
                'price_for_child'    => $validated['price_for_child'] ?? null,
                'discount_percentage'=> $validated['discount_percentage'] ?? 0,
                'conditions'         => $validated['conditions'] ?? null,
                'is_public'          => $validated['is_public'] ?? true,
                'policy_id'          => $validated['policy_id'] ?? null,
                'amenities'          => $validated['amenities'] ?? [],
                'coupons'            => $validated['coupons'] ?? [],
                'waypoints'          => $waypoints,
            ];

            $tripData = array_filter($tripData, fn($value) => $value !== null);

            if (!empty($validated['is_recurring']) && !empty($validated['days'])) {
                $tripData['repeat_config'] = [
                    'start_date' => $request->input('departure_time'),
                    'end_date'   => $validated['end_date_repeat'] ?? null,
                    'status'     => 'active',
                    'days'       => $validated['days'],
                ];
            }

            $result = $this->tripService->updateTrip($trip->uuid, $tripData, $user->id);

            if (!$result['success']) {
                return redirect()->back()->withInput()->with('error', $result['message']);
            }

            $redirectUrl = route('provider.trips.index', ['branch' => $branch->id]);
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'تم تحديث الرحلة بنجاح', 'redirect' => $redirectUrl]);
            }
            return redirect()->to($redirectUrl)->with('success', 'تم تحديث الرحلة بنجاح');
        } catch (\Exception $e) {
            Log::error('Provider trip update failed: ' . $e->getMessage());
            return back()->withInput()->with('error', 'حدث خطأ أثناء تحديث الرحلة. يرجى المحاولة مرة أخرى.');
        }
    }

    public function destroy(Request $request, ProviderBranch $branch, Trip $trip): RedirectResponse|JsonResponse
    {
        $this->authorize('access', $branch);

        if (!$trip->service || $trip->service->branch_id !== $branch->id) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'الرحلة غير موجودة أو لا تنتمي لهذا الفرع'], 403);
            }
            return redirect()->route('provider.branches.trips.index', $branch)
                ->with('error', 'الرحلة غير موجودة أو لا تنتمي لهذا الفرع');
        }

        $user = auth()->user();

        if ($trip->departure_time <= now()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'لا يمكن حذف رحلة انطلقت بالفعل'], 422);
            }
            return redirect()->back()->with('error', 'لا يمكن حذف رحلة انطلقت بالفعل');
        }

        if ($trip->bookings()->exists()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'لا يمكن حذف رحلة عليها حجوزات'], 422);
            }
            return redirect()->back()->with('error', 'لا يمكن حذف رحلة عليها حجوزات');
        }

        try {
            $result = $this->tripService->deleteTrip($trip->uuid, $user->id);

            if (!$result['success']) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $result['message']], 500);
                }
                return redirect()->route('provider.branches.trips.index', $branch)
                    ->with('error', $result['message']);
            }

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم حذف الرحلة بنجاح',
                    'redirect' => $request->header('referer') ?? route('provider.trips.all')
                ]);
            }

            return redirect()->back()->with('success', 'تم حذف الرحلة بنجاح');
        } catch (\Exception $e) {
            Log::error('Provider trip destroy failed: ' . $e->getMessage());

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'حدث خطأ أثناء حذف الرحلة'], 500);
            }

            return back()->with('error', 'حدث خطأ أثناء حذف الرحلة. يرجى المحاولة مرة أخرى.');
        }
    }
}