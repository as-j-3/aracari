<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Provider\DashboardFilterRequest;
use App\Services\ReportService;
use App\Services\ProviderContext;
use App\Models\Booking;
use App\Models\Trip;
use App\Models\ProviderBranch;
use Illuminate\View\View;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProviderDashboardController extends Controller
{
    private ReportService $reportService;
    private ProviderContext $providerContext;

    public function __construct(ReportService $reportService, ProviderContext $providerContext)
    {
        $this->reportService = $reportService;
        $this->providerContext = $providerContext;
    }


    public function index(DashboardFilterRequest $request): View
    {
        $user = $request->user();

        $providerId = $this->providerContext->providerUserIdFor($user);
        if (!$providerId) {
            abort(403, 'لا يوجد مزود خدمة مرتبط بحسابك');
        }
        $validated = $request->validated();

        $startDate = isset($validated['start_date'])
            ? Carbon::parse($validated['start_date'])->startOfDay()
            : Carbon::now()->subDays(30)->startOfDay();

        $endDate = isset($validated['end_date'])
            ? Carbon::parse($validated['end_date'])->endOfDay()
            : Carbon::now()->endOfDay();

        $cacheKey = "provider_dashboard_v2_{$providerId}_{$startDate->format('Y-m-d')}_{$endDate->format('Y-m-d')}";

        try {
            $data = Cache::remember($cacheKey, 300, function () use ($providerId, $startDate, $endDate) {
                $monthlyRevenue = $this->reportService->getMonthlyRevenueReport(Carbon::now()->year, $providerId);
                $tripPerformance = $this->reportService->getTripPerformanceReport($startDate, $endDate, $providerId);

                $recentBookings = Booking::whereHas('branch.provider', function ($q) use ($providerId) {
                    $q->where('user_id', $providerId);
                })->with(['user', 'status', 'tripReservations.trip.departureCity', 'tripReservations.trip.destinationCity'])
                    ->latest()
                    ->limit(6)
                    ->get();

                $totalRevenue = collect($monthlyRevenue)->sum('total_revenue');
                $totalBookings = collect($monthlyRevenue)->sum('total_bookings');

                $activeTrips = Trip::whereHas('vehicle', function ($q) use ($providerId) {
                    $q->where('user_id', $providerId);
                })->where('departure_time', '>=', now())->count();

                $completedTrips = Trip::whereHas('vehicle', function ($q) use ($providerId) {
                    $q->where('user_id', $providerId);
                })->where('departure_time', '<', now())->count();

                return [
                    'totalRevenue' => (float) $totalRevenue,
                    'totalBookings' => (int) $totalBookings,
                    'activeTrips' => $activeTrips,
                    'completedTrips' => $completedTrips,
                    'monthlyRevenue' => $monthlyRevenue,
                    'tripPerformance' => $tripPerformance,
                    'recentBookings' => $recentBookings,
                ];
            });

            return view('provider.dashboard', array_merge($data, [
                'startDate' => $startDate,
                'endDate' => $endDate,
            ]));

        } catch (\Exception $e) {
            Log::error('Provider dashboard failed: ' . $e->getMessage(), [
                'provider_id' => $providerId,
                'trace' => $e->getTraceAsString()
            ]);

            return view('provider.dashboard', [
                'totalRevenue' => 0,
                'totalBookings' => 0,
                'activeTrips' => 0,
                'completedTrips' => 0,
                'monthlyRevenue' => [],
                'tripPerformance' => [],
                'recentBookings' => collect([]),
                'startDate' => $startDate,
                'endDate' => $endDate,
            ])->with('error', 'حدث خطأ أثناء تحميل بيانات لوحة التحكم. يرجى المحاولة لاحقاً.');
        }
    }
}
