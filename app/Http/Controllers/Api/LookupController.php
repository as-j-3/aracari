<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Gender;
use App\Models\PaymentMethod as PaymentMethodModel;
use App\Models\TypeOfService;
use App\Models\TypeOfTrip;
use App\Models\Trip;
use App\Http\Resources\CityResource;
use App\Constants\CountryCodes;
use App\Http\Resources\TripResource;
use App\Models\Country;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use App\Repositories\Contracts\TripRepositoryInterface;
use Illuminate\Support\Facades\Cache;

class LookupController extends Controller
{
    use ApiResponse;

    public TripRepositoryInterface $tripRepository;
    public function __construct(TripRepositoryInterface $tripRepository)
    {
        $this->tripRepository = $tripRepository;
    }

    private const CACHE_TTL = 3600;

    public function genders(): JsonResponse
    {
        $data = Cache::remember('api:genders', self::CACHE_TTL, fn() =>
            Gender::select('id', 'name')->get()
        );
        return $this->success($data, "تم جلب أنواع الجنس");
    }

    public function cities(): JsonResponse
    {
        $perPage = request()->get('per_page', 50);
        $cities = City::select('id', 'name', 'country_id','uuid','image')
            ->with('country:id,name,uuid')
            ->orderBy('name')
            ->paginate($perPage);

        return $this->paginated($cities, CityResource::class, 'تم جلب المدن');
    }

    public function tripLocations(): JsonResponse
    {
        $result = Cache::remember('api:trip_locations', 600, function () {
            $trips = Trip::where('is_public', true)
                ->where('departure_time', '>', now())
                ->whereHas('tripStatus', fn($q) => $q->where('status_name', '!=', 'cancelled'))
                ->with([
                    'departureCity.country',
                    'destinationCity.country',
                    'departureStation',
                    'arrivalStation',
                ])
                ->get();

            if ($trips->isEmpty()) {
                return [];
            }

            $uniqueMap = [];
            foreach ($trips as $trip) {
                if ($trip->departureCity) {
                    $key = $trip->departureCity->uuid . '|' . ($trip->departureStation?->name ?? 'null');
                    if (!isset($uniqueMap[$key])) {
                        $uniqueMap[$key] = [
                            'uuid' => $trip->departureCity->uuid,
                            'name' => $trip->departureCity->name,
                            'country' => $trip->departureCity->country->name ?? null,
                            'country_uuid' => $trip->departureCity->country->uuid ?? null,
                            'station' => $trip->departureStation?->name,
                        ];
                    }
                }
                if ($trip->destinationCity) {
                    $key = $trip->destinationCity->uuid . '|' . ($trip->arrivalStation?->name ?? 'null');
                    if (!isset($uniqueMap[$key])) {
                        $uniqueMap[$key] = [
                            'uuid' => $trip->destinationCity->uuid,
                            'name' => $trip->destinationCity->name,
                            'country' => $trip->destinationCity->country->name ?? null,
                            'country_uuid' => $trip->destinationCity->country->uuid ?? null,
                            'station' => $trip->arrivalStation?->name,
                        ];
                    }
                }
            }
            return collect($uniqueMap)->values()->sortBy('name')->values()->toArray();
        });

        return $this->success($result, $result ? '' : 'لا توجد رحلات متاحة حالياً');
    } 

    public function paymentMethods(): JsonResponse
    {
        $methods = Cache::remember('api:payment_methods', self::CACHE_TTL, fn() =>
            PaymentMethodModel::select('id', 'method_name')->orderBy('method_name')->get()
        );
        return $this->success($methods, "تم جلب طرق الدفع");
    }

    public function amenities(): JsonResponse
    {
        $data = Cache::remember('api:amenities', self::CACHE_TTL, fn() =>
            \App\Models\Amenity::select('uuid', 'name')->get()
        );
        return $this->success($data, "تم جلب المرافق والخدمات");
    }

    public function tripTypes(): JsonResponse
    {
        $data = Cache::remember('api:trip_types', self::CACHE_TTL, fn() =>
            TypeOfTrip::select('id', 'type_name')->get()
        );
        return $this->success($data, "تم جلب أنواع الرحلات");
    }

    public function serviceTypes(): JsonResponse
    {
        $data = Cache::remember('api:service_types', self::CACHE_TTL, fn() =>
            TypeOfService::select('id', 'type_name')->get()
        );
        return $this->success($data, "تم جلب أنواع الخدمات التشغيلية");
    }

    public function validateCoupon(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|max:50',
            'trip_uuid' => 'required|exists:trips,uuid',
        ], [
            'code.required' => 'يرجى إدخال كود الكوبون',
            'trip_uuid.required' => 'يرجى إرسال UUID الرحلة',
            'trip_uuid.exists' => 'الرحلة غير موجودة',
        ]);

        $trip = Trip::where('uuid', $request->trip_uuid)
            ->where('is_public', true)
            ->where('departure_time', '>', now())
            ->first();

        if (!$trip) {
            return $this->success(['valid' => false, 'message' => 'الرحلة غير متاحة']);
        }

        $coupon = \App\Models\Coupon::where('code', $request->code)->first();

        if (!$coupon || !$coupon->isValid()) {
            return $this->success(['valid' => false, 'message' => 'الكوبون غير صالح أو منتهي الصلاحية أو وصل للحد الأقصى للاستخدام']);
        }

        $isApplicable = $trip->coupons()->where('coupons.id', $coupon->id)->exists();

        if (!$isApplicable) {
            return $this->success(['valid' => false, 'message' => 'الكوبون لا ينطبق على هذه الرحلة']);
        }

        return $this->success([
            'valid' => true,
            'code' => $coupon->code,
            'percentage' => (float) $coupon->percentage,
            'minimum_amount' => $coupon->minimum_amount ? (float) $coupon->minimum_amount : null,
            'message' => 'الكوبون صالح للاستخدام على هذه الرحلة',
        ]);
    }

    public function countryCodes(): JsonResponse
    {
        $data = Cache::remember('api:country_codes', 86400, function () {
            $result = [];
            foreach (CountryCodes::all() as $code => $name) {
                $result[] = [
                    'code' => $code,
                    'name' => CountryCodes::getArabicLabel($code),
                ];
            }
            return $result;
        });

        return $this->success($data, 'تم جلب أكواد الدول');
    }


    public function upcomingTripsByCountry(Request $request): JsonResponse
    {
        $request->validate([
            'iso_code' => 'nullable|string|size:2',
        ], [
            'iso_code.size' => 'كود الدولة يجب أن يكون حرفين (مثال: YE)',
        ]);

        $isoCode = strtoupper($request->input('iso_code') ?: 'YE');

        $country = Country::where('iso_code', $isoCode)->first();

        if (!$country) {
            return $this->notFound('الدولة غير موجودة');
        }

        $perPage = $request->integer('per_page', 15);

        $trips = $this->tripRepository->getUpcomingTripsByCountry($isoCode, $perPage);

        return $this->paginated($trips, TripResource::class, 'تم جلب الرحلات القادمة');
    }
}