<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Trip\SearchTripRequest;
use App\Http\Requests\Api\Trip\AvailableTripsRequest;
use App\Http\Requests\Api\Trip\ReturnTripOptionsRequest;
use App\Http\Resources\TripResource;
use \App\Http\Resources\PassengerResource;
use App\Services\ReturnTripSearchService;
use App\Services\TripService;
use App\Traits\ApiResponse;
use App\Helpers\UuidHelper;
use App\Services\BookingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Trip;
use App\Constants\TripStatus;
use Illuminate\Support\Carbon;

class TripController extends Controller
{
    use ApiResponse;
    protected BookingService $bookingService;
    protected TripService $tripService;
    protected ReturnTripSearchService $returnTripSearch;


    public function __construct(
        TripService $tripService,
        BookingService $bookingService,
        ReturnTripSearchService $returnTripSearch,
    ) {
        $this->tripService = $tripService;
        $this->bookingService = $bookingService;
        $this->returnTripSearch = $returnTripSearch;
    }


    public function index(SearchTripRequest $request): JsonResponse
    {
        $filters = $request->validated();

        // Convert UUID to ID for city filters since Trip table uses ID
        if (!empty($filters['from_city'])) {
            $filters['from_city'] = UuidHelper::uuidToId('cities', $filters['from_city']);
        }
        if (!empty($filters['to_city'])) {
            $filters['to_city'] = UuidHelper::uuidToId('cities', $filters['to_city']);
        }
        if (!empty($filters['provider_id'])) {
            $filters['provider_id'] = UuidHelper::uuidToId('users', $filters['provider_id']);
        }
        $filters['publicOnly'] = true; // Security: Only return public trips via API
        $perPage = $request->get('per_page', 15);
        $trips = $this->tripService->getAvailableTrips($filters, $perPage);

        return $this->paginated($trips, TripResource::class, 'تم جلب الرحلات بنجاح');
    }


    public function available(AvailableTripsRequest $request): JsonResponse
    {
        $filters = $request->validated();

        if (!empty($filters['from_city'])) {
            $filters['from_city'] = UuidHelper::uuidToId('cities', $filters['from_city']);
        }
        if (!empty($filters['to_city'])) {
            $filters['to_city'] = UuidHelper::uuidToId('cities', $filters['to_city']);
        }
        if (!empty($filters['provider_id'])) {
            $filters['provider_id'] = UuidHelper::uuidToId('users', $filters['provider_id']);
        }
        $filters['publicOnly'] = true; 
        $perPage = $request->get('per_page', 15);
        $trips = $this->tripService->getAvailableTrips($filters, $perPage);

         $message = $trips->total() > 0
        ? 'تم جلب الرحلات المتاحة بنجاح'
        : 'لا توجد رحلات متاحة';

        return $this->paginated($trips, TripResource::class, $message);
    }


    public function show(Request $request,string $uuid): JsonResponse
    {
        $trip = $this->tripService->getTripByUuid($uuid, true);

        if (!$trip) {
            return $this->notFound('الرحلة غير موجودة');
        }
        
        $previousPassengers = auth('sanctum')->check()
            ? $this->bookingService->getUserPreviousPassengers(auth('sanctum')->id())
            : collect();    

        
        $trip->load(['vehicle', 'typeOfTrip', 'levelTrip', 'tripStatus', 'departureCity.country', 'destinationCity.country', 'tripAmenities.amenity', 'policies', 'repeats.repeatedDays']);

        return $this->success([
            'trip' => new TripResource($trip),
            'previous_passengers' => PassengerResource::collection($previousPassengers),
        ]);
    }


    public function checkAvailability(string $uuid): JsonResponse
    {
        $result = $this->tripService->checkAvailability($uuid);

        if (!$result['success']) {
            return $this->notFound($result['message']);
        }

        return $this->success($result['data']);
    }


    public function returnOptions(ReturnTripOptionsRequest $request): JsonResponse
    {
        $outboundTrip = Trip::where('uuid', $request->outbound_trip_uuid)->first();

        if (!$outboundTrip) {
            return $this->notFound('رحلة الذهاب غير موجودة');
        }

        $targetDate = Carbon::parse($request->date)->startOfDay();

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
                'uuid'             => $rt->uuid,
                'departure_time'   => $departure->format('h:i A'),
                'departure_date'   => $departure->format('d/m/Y'),
                'arrival_time'     => $arrival->format('h:i A'),
                'price_adult'      => (float) $priceAdult,
                'price_child'      => (float) $priceChild,
                'available_seats'  => $rt->available_seats,
                'vehicle_type'     => $rt->vehicle && $rt->vehicle->vehicleType
                    ? $rt->vehicle->vehicleType->type_name : '',
                'provider_name'    => $rt->user && $rt->user->providerService
                    ? $rt->user->providerService->name
                    : ($rt->user ? $rt->user->name : ''),
                'is_same_provider' => $rt->user_id === $outboundTrip->user_id,
                'is_recurring'     => $rt->parent_id !== null,
                'discount'         => $hasDiscount ? (float) $rt->discount_percentage : 0,
            ];
        });

        return $this->success([
            'trips'    => $formatted,
            'count'    => $formatted->count(),
            'date'     => $targetDate->toDateString(),
            'min_date' => $this->returnTripSearch->minSearchDate($outboundTrip),
        ]);
    }
}