<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use App\Constants\BookingStatus as BookingStatusConst;
use App\Constants\TripStatus as TripStatusConst;

class ReportOptimizationService
{
    private const CACHE_DURATION = 3600; // 1 hour cache duration
    private const CACHE_KEYS_TRACKER = 'report_cache_keys';

    /**
     * Initialize report optimization service
     * 
     * This service provides optimized reporting capabilities with caching
     * and chunked processing for large datasets.
     */

    /**
     * Store cache key for later clearing
     */
    private function rememberCacheKey(string $key): void
    {
        $keys = Cache::get(self::CACHE_KEYS_TRACKER, []);
        $keys[] = $key;
        Cache::put(self::CACHE_KEYS_TRACKER, array_unique($keys), self::CACHE_DURATION + 3600);
    }
    
    /**
     * Retrieve booking statistics with caching
     *
     * This method provides comprehensive booking statistics for a given
     * date range with caching to optimize performance.
     *
     * Performance optimization:
     * - Caching for 1 hour to reduce database load
     * - Single query with aggregations where possible
     * - Efficient date range filtering
     *
     * Statistics include:
     * - Total bookings count
     * - Confirmed and cancelled bookings
     * - Total revenue
     * - Active bookings
     * - Average booking value
     * - Net profit
     * - Confirmation rate
     *
     * @param Carbon $startDate Start date for statistics
     * @param Carbon $endDate End date for statistics
     * @param int|null $providerId Provider ID for filtering (optional)
     * @return array Comprehensive booking statistics
     */
    public function getBookingStats(Carbon $startDate, Carbon $endDate, ?int $providerId = null): array
    {
        $cacheKey = "booking_stats_{$startDate->format('Y-m-d')}_{$endDate->format('Y-m-d')}_{$providerId}";
        $this->rememberCacheKey($cacheKey);

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($startDate, $endDate, $providerId) {
            $baseQuery = DB::table('bookings')
                ->whereBetween('bookings.created_at', [$startDate, $endDate]);

            if ($providerId) {
                $baseQuery->join('trip_reservations', 'bookings.id', '=', 'trip_reservations.booking_id')
                    ->join('trips', 'trip_reservations.trip_id', '=', 'trips.id')
                    ->join('vehicles', 'trips.vehicle_id', '=', 'vehicles.id')
                    ->where('vehicles.user_id', $providerId);
            }

            $totalBookings = (clone $baseQuery)->count();
            $confirmedBookings = (clone $baseQuery)->where('booking_status_id', BookingStatusConst::CONFIRMED)->count();

            $confirmationRate = $totalBookings > 0 ? ($confirmedBookings / $totalBookings) * 100 : 0;

            return [
                'total' => $totalBookings,
                'total_bookings' => $totalBookings,
                'confirmed' => $confirmedBookings,
                'cancelled' => (clone $baseQuery)->where('booking_status_id', BookingStatusConst::CANCELLED)->count(),
                'total_revenue' => (clone $baseQuery)->sum('amount'),
                'active_bookings' => (clone $baseQuery)->where('booking_status_id', '!=', BookingStatusConst::CANCELLED)->count(),
                'cancelled_bookings' => (clone $baseQuery)->where('booking_status_id', BookingStatusConst::CANCELLED)->count(),
                'average_booking_value' => (clone $baseQuery)->avg('amount'),
                'net_profit' => (clone $baseQuery)->where('booking_status_id', '!=', BookingStatusConst::CANCELLED)->sum('amount'),
                'confirmation_rate' => round($confirmationRate, 2),
            ];
        });
    }
    
    /**
     * Retrieve optimized trips report with booking aggregation
     *
     * This method generates a trips report with booking counts and
     * revenue aggregation using optimized SQL queries.
     *
     * Performance optimization:
     * - Uses SQL aggregations (COUNT, SUM) instead of N+1 queries
     * - Left joins for optional relationships
     * - Group by for efficient aggregation
     * - Limit to prevent excessive data retrieval
     *
     * @param Carbon $startDate Start date for report
     * @param Carbon $endDate End date for report
     * @param int|null $providerId Provider ID for filtering (optional)
     * @param int $limit Maximum number of trips to return (default 50)
     * @return \Illuminate\Support\Collection Trips with booking statistics
     */
    public function getTripsReport(Carbon $startDate, Carbon $endDate, ?int $providerId = null, int $limit = 50): \Illuminate\Support\Collection
    {
        $query = DB::table('trips')
            ->select([
                'trips.id',
                'trips.uuid',
                'trips.departure_city',
                'trips.destination_city',
                'trips.departure_time',
                DB::raw('COUNT(DISTINCT bookings.id) as bookings_count'),
                DB::raw('SUM(bookings.amount) as total_revenue'),
                'dc.name as departure_city_name',  //  أضف
                'ac.name as destination_city_name', //  أضف
            ])
            ->leftJoin('trip_reservations', 'trips.id', '=', 'trip_reservations.trip_id')
            ->leftJoin('bookings', 'trip_reservations.booking_id', '=', 'bookings.id')
            ->leftJoin('cities as dc', 'trips.departure_city', '=', 'dc.id')       //  أضف
            ->leftJoin('cities as ac', 'trips.destination_city', '=', 'ac.id')     //  أضف
            ->whereBetween('trips.departure_time', [$startDate, $endDate])
            ->where('trips.trip_status_id', '!=', TripStatusConst::CANCELLED);

        if ($providerId) {
            $query->join('vehicles', 'trips.vehicle_id', '=', 'vehicles.id')
                ->where('vehicles.user_id', $providerId);
        }

        return $query->groupBy('trips.id', 'trips.uuid', 'trips.departure_city', 'trips.destination_city', 'trips.departure_time', 'dc.name', 'ac.name')
            ->orderBy('bookings_count', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($trip) {
                return [
                    'uuid' => $trip->uuid,
                    'route' => ($trip->departure_city_name ?? '?') . ' → ' . ($trip->destination_city_name ?? '?'), //  أسماء
                    'bookings_count' => $trip->bookings_count,
                    'total_revenue' => $trip->total_revenue ?? 0,
                    'departure_time' => $trip->departure_time,
                ];
            });
    }    
    /**
     * Retrieve payment methods report with caching
     *
     * This method provides payment method usage statistics with
     * caching for improved performance.
     *
     * Performance optimization:
     * - Caching for 1 hour
     * - SQL aggregations for count and sum
     * - Efficient joins
     *
     * @param Carbon $startDate Start date for report
     * @param Carbon $endDate End date for report
     * @param int|null $providerId Provider ID for filtering (optional)
     * @return \Illuminate\Support\Collection Payment methods with usage statistics
     */
    public function getPaymentMethodsReport(Carbon $startDate, Carbon $endDate, ?int $providerId = null): \Illuminate\Support\Collection
    {
        $cacheKey = "payment_methods_report_{$startDate->format('Y-m-d')}_{$providerId}";
        $this->rememberCacheKey($cacheKey);

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($startDate, $endDate, $providerId) {
            $query = DB::table('booking_payments')
                ->select([
                    'payment_methods.id',
                    'payment_methods.method_name',
                    DB::raw('COUNT(booking_payments.id) as count'),
                    DB::raw('SUM(booking_payments.payed_amount) as total'),
                ])
                ->join('payment_methods', 'booking_payments.payment_method_id', '=', 'payment_methods.id')
                ->join('bookings', 'booking_payments.booking_id', '=', 'bookings.id')
                ->whereBetween('bookings.created_at', [$startDate, $endDate]);

            //  Filter by provider for non-admins
            if ($providerId) {
                $query->join('trip_reservations', 'bookings.id', '=', 'trip_reservations.booking_id')
                    ->join('trips', 'trip_reservations.trip_id', '=', 'trips.id')
                    ->join('vehicles', 'trips.vehicle_id', '=', 'vehicles.id')
                    ->where('vehicles.user_id', $providerId);
            }

            return $query->groupBy('payment_methods.id', 'payment_methods.method_name')
                ->orderBy('total', 'desc')
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'method_name' => $item->method_name,
                        'count' => $item->count,
                        'total' => $item->total,
                    ];
                });
        });
    }
    
    /**
     * Export bookings using chunked processing to avoid memory issues
     *
     * This method processes large booking datasets in chunks to prevent
     * memory exhaustion during export operations.
     *
     * Performance optimization:
     * - Chunked processing (1000 records per chunk)
     * - Efficient SQL joins
     * - Memory-efficient callback processing
     *
     * @param Carbon $startDate Start date for export
     * @param Carbon $endDate End date for export
     * @param callable $processor Callback function to process each booking
     * @param int|null $providerId Provider ID for filtering (optional)
     * @return void
     */
    public function exportBookingsChunked(Carbon $startDate, Carbon $endDate, callable $processor, ?int $providerId = null): void
    {
        $query = DB::table('bookings')
            ->select([
                'bookings.uuid',
                'bookings.amount',
                'bookings.created_at',
                'users.name as user_name',
                'users.email as user_email',
                'booking_statuses.status_name',
            ])
            ->join('users', 'bookings.user_id', '=', 'users.id')
            ->join('booking_statuses', 'bookings.booking_status_id', '=', 'booking_statuses.id')
            ->whereBetween('bookings.created_at', [$startDate, $endDate])
            ->orderBy('bookings.created_at');

        
        if ($providerId) {
            $query->join('trip_reservations', 'bookings.id', '=', 'trip_reservations.booking_id')
                ->join('trips', 'trip_reservations.trip_id', '=', 'trips.id')
                ->join('vehicles', 'trips.vehicle_id', '=', 'vehicles.id')
                ->where('vehicles.user_id', $providerId);
        }

        $query->chunk(1000, function ($bookings) use ($processor) {
            foreach ($bookings as $booking) {
                $processor($booking);
            }
        });
    }
    
    /**
     * Retrieve optimized dashboard data with caching
     *
     * This method aggregates all dashboard data including statistics,
     * popular trips, recent bookings, and revenue charts.
     *
     * Performance optimization:
     * - Caching for 1 hour
     * - Aggregates multiple data sources in one call
     * - Delegates to specialized methods for each data type
     *
     * @return array Complete dashboard data including stats, trips, bookings, and charts
     */
    public function getDashboardData(): array
    {
        $this->rememberCacheKey('dashboard_data');
        
        return Cache::remember('dashboard_data', self::CACHE_DURATION, function () { // 1 hour
            $today = Carbon::today();
            $thisMonth = Carbon::now()->startOfMonth();
            
            return [
                'today_stats' => $this->getBookingStats($today, Carbon::now()),
                'month_stats' => $this->getBookingStats($thisMonth, Carbon::now()),
                'popular_trips' => $this->getPopularTrips(10),
                'recent_bookings' => $this->getRecentBookings(20),
                'revenue_chart' => $this->getRevenueChart(30), // آخر 30 يوم
            ];
        });
    }
    
    /**
     * Retrieve popular trips based on booking count
     *
     * This method identifies the most popular routes based on
     * booking frequency and average booking value.
     *
     * Performance optimization:
     * - SQL aggregations for count and average
     * - Efficient joins
     * - Time-based filtering (last 30 days)
     *
     * @param int $limit Maximum number of popular trips to return
     * @param int|null $providerId Provider ID for filtering (optional)
     * @return \Illuminate\Support\Collection Popular trips with statistics
     */
    private function getPopularTrips(int $limit, ?int $providerId = null): \Illuminate\Support\Collection
    {
        $query = DB::table('trips')
            ->select([
                'trips.departure_city',
                'trips.destination_city',
                DB::raw('COUNT(*) as trip_count'),
                DB::raw('AVG(bookings.amount) as avg_amount'),
            ])
            ->join('trip_reservations', 'trips.id', '=', 'trip_reservations.trip_id')
            ->join('bookings', 'trip_reservations.booking_id', '=', 'bookings.id')
            ->where('trips.departure_time', '>=', Carbon::now()->subDays(30))
            ->where('trips.is_public', true);

       
        if ($providerId) {
            $query->join('vehicles', 'trips.vehicle_id', '=', 'vehicles.id')
                ->where('vehicles.user_id', $providerId);
        }

        return $query->groupBy('trips.departure_city', 'trips.destination_city')
            ->orderBy('trip_count', 'desc')
            ->limit($limit)
            ->get();
    }
    
    /**
     * Retrieve recent bookings with user and status information
     *
     * This method fetches the most recent bookings with related
     * user and status data for dashboard display.
     *
     * Performance optimization:
     * - Efficient SQL joins
     * - Limit to prevent excessive data retrieval
     * - Ordered by creation date (newest first)
     *
     * @param int $limit Maximum number of recent bookings to return
     * @param int|null $providerId Provider ID for filtering (optional)
     * @return \Illuminate\Support\Collection Recent bookings with user and status
     */
    private function getRecentBookings(int $limit, ?int $providerId = null): \Illuminate\Support\Collection
    {
        $query = DB::table('bookings')
            ->select([
                'bookings.uuid',
                'bookings.amount',
                'bookings.created_at',
                'users.name as user_name',
                'booking_statuses.status_name',
            ])
            ->join('users', 'bookings.user_id', '=', 'users.id')
            ->join('booking_statuses', 'bookings.booking_status_id', '=', 'booking_statuses.id')
            ->orderBy('bookings.created_at', 'desc');

        
        if ($providerId) {
            $query->join('trip_reservations', 'bookings.id', '=', 'trip_reservations.booking_id')
                ->join('trips', 'trip_reservations.trip_id', '=', 'trips.id')
                ->join('vehicles', 'trips.vehicle_id', '=', 'vehicles.id')
                ->where('vehicles.user_id', $providerId);
        }

        return $query->limit($limit)->get();
    }
    
    /**
     * Retrieve revenue chart data for specified number of days
     *
     * This method generates daily revenue data for the specified
     * time period for chart visualization.
     *
     * Performance optimization:
     * - Single optimized query with GROUP BY (reduces N queries to 1)
     * - Date-based aggregation at database level
     * - Returns structured data for chart rendering
     *
     * @param int $days Number of days to include in chart
     * @param int|null $providerId Provider ID for filtering (optional)
     * @return array Daily revenue data with dates and amounts
     */
    private function getRevenueChart(int $days, ?int $providerId = null): array
    {
        $startDate = Carbon::now()->subDays($days - 1)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        // Single optimized query with GROUP BY
        // Performance: O(1) instead of O(N) database queries
        $query = DB::table('bookings')
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(amount) as revenue')
            )
            ->whereBetween('created_at', [$startDate, $endDate]);

        
        if ($providerId) {
            $query->join('trip_reservations', 'bookings.id', '=', 'trip_reservations.booking_id')
                ->join('trips', 'trip_reservations.trip_id', '=', 'trips.id')
                ->join('vehicles', 'trips.vehicle_id', '=', 'vehicles.id')
                ->where('vehicles.user_id', $providerId);
        }

        $revenueData = $query->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date', 'asc')
            ->get()
            ->keyBy('date')
            ->toArray();
        
        // Fill in missing dates with zero revenue
        $data = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $data[] = [
                'date' => $date,
                'revenue' => $revenueData[$date]->revenue ?? 0,
            ];
        }
        
        return $data;
    }
    
    /**
     * Retrieve optimized lookup data with caching
     * 
     * This method provides all lookup/reference data (cities, countries,
     * vehicle types, amenities) with caching for improved performance.
     * 
     * Performance optimization:
     * - Caching for 1 hour
     * - Single call for all lookup data
     * - Efficient ordered queries
     * 
     * @return array Lookup data including cities, countries, vehicle types, and amenities
     */
    public function getOptimizedLookups(): array
    {
        $this->rememberCacheKey('lookups_data');

        return Cache::remember('lookups_data', self::CACHE_DURATION, function () {
            return [
                'cities' => DB::table('cities')
                    ->select('id', 'name', 'country_id')
                    ->orderBy('name')
                    ->get(),
                    
                'countries' => DB::table('countries')
                    ->select('id', 'name')
                    ->orderBy('name')
                    ->get(),
                    
                'vehicle_types' => DB::table('vehicle_types')
                    ->select('id', 'type_name')
                    ->orderBy('type_name')
                    ->get(),
                    
                'amenities' => DB::table('amenities')
                    ->select('id', 'name', 'icon')
                    ->orderBy('name')
                    ->get(),
            ];
        });
    }
    
    /**
     * Clear report cache when important data is updated
     * 
     * This method invalidates cached report data to ensure fresh
     * data is displayed after important updates.
     * 
     * Cache patterns cleared:
     * - Booking statistics
     * - Payment methods report
     * - Dashboard data
     * - Lookup data
     * 
     * @return void
     */
    public function clearReportCache(): void
    {
        $keys = Cache::get(self::CACHE_KEYS_TRACKER, []);
        
        foreach ($keys as $key) {
            Cache::forget($key);
        }
        
        Cache::forget(self::CACHE_KEYS_TRACKER);
    }
}
