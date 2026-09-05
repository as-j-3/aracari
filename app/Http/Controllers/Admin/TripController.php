<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Trip\IndexTripRequest;
use App\Http\Resources\Admin\TripSummaryResource;
use App\Http\Resources\TripResource;
use App\Services\TripService;
use App\Services\CurrencyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TripController extends Controller
{
    protected TripService $tripService;
    protected CurrencyService $currencyService;

    public function __construct(
        TripService $tripService,
        CurrencyService $currencyService
    ) {
        $this->tripService = $tripService;
        $this->currencyService = $currencyService;
        $this->middleware('require.admin');
    }

    public function index(IndexTripRequest $request): JsonResponse
    {
        /*
        // الكود السابق (Web View):
        $filters = $request->only(['search', 'status', 'city', 'date']);
        $perPage = $request->get('per_page', 15);
        $trips = $this->tripService->getAllTrips($filters, $perPage);
        $trips->load(['vehicle.user.providerService', 'departureCity', 'destinationCity', 'coupons']);
        $baseCurrency = $this->currencyService->baseCurrency();

        return view('admin.trips.index', compact('trips', 'baseCurrency'));
        */

        // كود الـ API:
        $filters = $request->validated();
        $perPage = (int) $request->get('per_page', 15);
        $trips = $this->tripService->getAllTrips($filters, $perPage);
        $trips->load(['vehicle.user.providerService', 'departureCity', 'destinationCity', 'coupons']);

        // حساب الإحصائيات الأربع (المطابقة لبطاقات الواجهة)
        $totalTrips = $trips->total();
        $activeTrips = $trips->getCollection()->filter(function ($t) {
            return in_array($t->trip_status_id, [\App\Constants\TripStatus::SCHEDULED, \App\Constants\TripStatus::IN_PROGRESS]);
        })->count();

        $todayTrips = $trips->getCollection()->filter(function ($t) {
            return $t->departure_time && \Carbon\Carbon::parse($t->departure_time)->isToday();
        })->count();

        $occupancySum = 0;
        $occupancyCount = 0;
        foreach ($trips->getCollection() as $t) {
            $seatTotal = $t->total_seats ?? ($t->ticket_limit ?? ($t->vehicle?->seats_count ?? 0));
            $available = $t->available_seats ?? 0;
            if ($seatTotal > 0) {
                $occupancySum += (($seatTotal - $available) / $seatTotal) * 100;
                $occupancyCount++;
            }
        }
        $occupancyPercent = $occupancyCount > 0 ? round($occupancySum / $occupancyCount) : 0;

        $statistics = [
            'total_trips'       => $totalTrips,
            'active_trips'      => $activeTrips,
            'today_trips'       => $todayTrips,
            'occupancy_percent' => $occupancyPercent,
            'occupancy_badge'   => $occupancyPercent > 70 ? 'ممتاز' : 'جيد',
        ];

        $baseCurrency = $this->currencyService->baseCurrency();

        return $this->success([
            'statistics'    => $statistics,
            'trips'         => TripSummaryResource::collection($trips)->response()->getData(true),
            'base_currency' => $baseCurrency,
        ], 'تم جلب بيانات الرحلات بنجاح');
    }

    public function show(Request $request, string $uuid): JsonResponse
    {
        /*
        // الكود السابق (Web View):
        $trip = $this->tripService->getTripByUuid($uuid);
        if (!$trip) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'الرحلة التي تحاول الوصول لها غير موجودة'], 404);
            }
            return redirect()->route('admin.trips.index')->with('error', 'الرحلة التي تحاول الوصول لها غير موجودة');
        }
        $baseCurrency = $this->currencyService->baseCurrency();

        return view('admin.trips.show', compact('trip', 'baseCurrency'));
        */

        // كود الـ API:
        $trip = $this->tripService->getTripByUuid($uuid);
        if (!$trip) {
            return $this->error('الرحلة التي تحاول الوصول لها غير موجودة', null, 404);
        }

        $trip->load([
            'vehicle.user.providerService',
            'departureCity',
            'destinationCity',
            'coupons',
            'typeOfTrip',
            'levelTrip',
            'tripStatus',
            'tripAmenities.amenity',
            'policies',
            'departureStation',
            'currency',
            'arrivalStation',
            'waypoints.city',
            'repeats.repeatedDays'
        ]);

        $baseCurrency = $this->currencyService->baseCurrency();

        return $this->success([
            'trip'          => new TripResource($trip),
            'base_currency' => $baseCurrency,
        ], 'تم جلب تفاصيل الرحلة بنجاح');
    }
}
