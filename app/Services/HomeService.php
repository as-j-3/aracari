<?php

namespace App\Services;

use App\Constants\TripStatus;
use App\Constants\UserTypes;
use App\Constants\UserStatus;
use App\Models\Trip;
use App\Models\City;
use App\Models\Vehicle;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
class HomeService
{
    /**
     * الحصول على بيانات الصفحة الرئيسية
     */
    public function getHomePageData(): array
    {
        return [
            'trips' => $this->getActiveTrips(),
            'popular_cities' => $this->getPopularCities(),
            'stats' => $this->getHomeStats(),
        ];
    }

    /**
     * البحث عن الرحلات
     */
    public function searchTrips(array $filters): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = Trip::with(['departureCity', 'destinationCity', 'vehicle.vehicleType', 'vehicle.seats', 'user.providerService', 'amenities', 'levelTrip', 'departureStation', 'arrivalStation', 'coupons'])
            ->where('departure_time', '>=', now())
            ->where('is_public', true)
            ->whereIn('trip_status_id', [TripStatus::SCHEDULED, TripStatus::IN_PROGRESS])
            ->where(function($q) {
                $q->where('available_seats', '>', 0)
                ->orWhereNull('ticket_limit');
            });

        // تطبيق الفلاتر
        // Convert UUID to ID for cities since Trip table uses ID
        if (!empty($filters['from_city'])) {
            $fromCityId = \App\Helpers\UuidHelper::uuidToId('cities', $filters['from_city']);
            if ($fromCityId) {
                $query->where('departure_city', $fromCityId);
            }
        }

        if (!empty($filters['to_city'])) {
            $toCityId = \App\Helpers\UuidHelper::uuidToId('cities', $filters['to_city']);
            if ($toCityId) {
                $query->where('destination_city', $toCityId);
            }
        }

        if (!empty($filters['departure_date'])) {
            $query->whereDate('departure_time', $filters['departure_date']);
        }

        if (!empty($filters['vehicle_types'])) {
            $query->whereHas('vehicle', function($q) use ($filters) {
                $q->whereIn('vehicle_type_id', $filters['vehicle_types']);
            });
        }

        if (!empty($filters['min_price'])) {
            $query->where('price_for_adult', '>=', $filters['min_price']);
        }

        if (!empty($filters['max_price'])) {
            $query->where('price_for_adult', '<=', $filters['max_price']);
        }

        // فلتر مزود الخدمة: التحقق أن المستخدم فعلًا مزود نشط ومعتمد
        if (!empty($filters['provider'])) {
            $providerId = (int) $filters['provider'];
            $isValidProvider = User::where('id', $providerId)
                ->where('type_user_code', UserTypes::PROVIDER)
                ->where('status_id', UserStatus::ACTIVE)
                ->where('is_approved', true)
                ->exists();

            if ($isValidProvider) {
                $query->where('created_by_user', $providerId);
            }
        }

        // الترتيب
        $sort = $filters['sort'] ?? 'departure_asc';
        switch ($sort) {
            case 'price_asc':
                $query->orderBy('price_for_adult', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price_for_adult', 'desc');
                break;
            case 'departure_asc':
                $query->orderBy('departure_time', 'asc');
                break;
            case 'departure_desc':
                $query->orderBy('departure_time', 'desc');
                break;
            default:
                $query->orderBy('departure_time', 'asc');
        }
        return $query->paginate(12);
    }

    /**
     * الحصول على تفاصيل الرحلة
     */
    public function getTripDetails(string $uuid): ?Trip
    {
        // L5: Use firstOrFail for automatic 404 handling
        return Trip::with(['departureCity', 'destinationCity', 'vehicle.seats', 'amenities', 'coupons'])
            ->where('uuid', $uuid)
            ->where('is_public', true)
            // Use trip_status_id with numeric values instead of status
            ->whereIn('trip_status_id', [TripStatus::SCHEDULED, TripStatus::IN_PROGRESS])
            ->firstOrFail();
    }

    /**
     * الحصول على رحلات مشابهة
     */
    public function getSimilarTrips(Trip $trip, int $limit = 4): \Illuminate\Database\Eloquent\Collection
    {
        return Trip::with(['departureCity', 'destinationCity', 'vehicle'])
            ->where('id', '!=', $trip->id)
            ->where('departure_city', $trip->departure_city)
            ->where('destination_city', $trip->destination_city)
            ->where('departure_time', '>=', now())
            ->where('is_public', true)
            ->where(function($q) {
                $q->where('available_seats', '>', 0)
                ->orWhereNull('ticket_limit');
            })
            ->take($limit)
            ->get();
    }
    /**
     * الحصول على الرحلات النشطة
     */
    private function getActiveTrips(): \Illuminate\Database\Eloquent\Collection
    {
        return Trip::with(['departureCity', 'destinationCity', 'vehicle', 'coupons'])
            ->where('departure_time', '>=', now())
            ->where('is_public', true)
            ->where(function($q) {
                $q->where('available_seats', '>', 0)
                ->orWhereNull('ticket_limit');
            })
            ->orderBy('departure_time', 'asc')
            ->take(12)
            ->get();
    }
    /**
     * الحصول على المدن الأكثر شعبية
     */
    private function getPopularCities(): \Illuminate\Database\Eloquent\Collection
    {
        return City::with(['departingTrips' => function($query) {
            $query->where('departure_time', '>=', now())
                  ->where('is_public', true)
                  ->whereIn('trip_status_id', [TripStatus::SCHEDULED, TripStatus::IN_PROGRESS]);
        }])
        ->withCount('departingTrips')
        ->orderBy('departing_trips_count', 'desc')
        ->take(8)
        ->get(['id', 'name', 'country_id', 'image']);
    }

    /**
     * الحصول على إحصائيات الصفحة الرئيسية - رحلات قادمة فقط
     */
    private function getHomeStats(): array
    {
        return Cache::remember('home_stats', 1800, function () {
            $now = now();
            
            return [
                'total_trips' => Trip::where('departure_time', '>=', $now)
                    ->where('is_public', true)
                    ->whereIn('trip_status_id', [TripStatus::SCHEDULED, TripStatus::IN_PROGRESS])
                    ->count(),
                'total_cities' => City::count(),
                'total_vehicles' => Vehicle::where('status', 'active')->count(),
                'today_trips' => Trip::whereDate('departure_time', today())->where('is_public', true)->count(),
                'total_vehicle_capacity' => Trip::where('departure_time', '>=', $now)
                    ->where('is_public', true)
                    ->join('vehicles', 'trips.vehicle_id', '=', 'vehicles.id')
                    ->sum('vehicles.seats_count'),
            ];
        });
    }
}
