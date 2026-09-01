<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Provider\DashboardFilterRequest;
use App\Services\ReportService;
use App\Services\ProviderContext;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;


class ProviderReportController extends Controller
{
    private ReportService $reportService;
    private ProviderContext $providerContext;

    private const CACHE_TTL = 600;

    public function __construct(ReportService $reportService, ProviderContext $providerContext)
    {
        $this->reportService = $reportService;
        $this->providerContext = $providerContext;
    }


    public function revenue(Request $request)
    {
        try {
            $user = $request->user();
            $providerId = $this->providerContext->providerUserIdFor($user);

            if (!$providerId) {
                abort(403, 'لا يوجد مزود خدمة مرتبط بحسابك');
            }

            $year = (int) $request->get('year', Carbon::now()->year);

            // Validate year range (prevent unrealistic queries)
            if ($year < 2020 || $year > Carbon::now()->year + 1) {
                $year = Carbon::now()->year;
            }

            $cacheKey = "provider_revenue_report_{$providerId}_{$year}";

            $data = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($year, $providerId) {
                $monthlyRevenue = $this->reportService->getMonthlyRevenueReport($year, $providerId);

                $summary = [
                    'total_revenue' => collect($monthlyRevenue)->sum('total_revenue'),
                    'total_bookings' => collect($monthlyRevenue)->sum('total_bookings'),
                    'average_booking_value' => collect($monthlyRevenue)->avg('average_booking_value') ?? 0,
                ];

                return compact('monthlyRevenue', 'summary');
            });

            return view('provider.reports.revenue', array_merge($data, compact('year')));
        } catch (\Exception $e) {
            Log::error('Provider revenue report failed: ' . $e->getMessage(), [
                'user_id' => $request->user()?->id,
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->with('error', 'حدث خطأ أثناء توليد تقرير الإيرادات. يرجى المحاولة لاحقاً.');
        }
    }


    public function bookings(DashboardFilterRequest $request)
    {
        try {
            $user = $request->user();
            $providerId = $this->providerContext->providerUserIdFor($user);

            if (!$providerId) {
                abort(403, 'لا يوجد مزود خدمة مرتبط بحسابك');
            }

            [$startDate, $endDate] = $this->getDateRange($request->validated());

            $cacheKey = "provider_bookings_report_{$providerId}_{$startDate->format('Y-m-d')}_{$endDate->format('Y-m-d')}";

            $bookingStats = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($startDate, $endDate, $providerId) {
                return $this->reportService->getBookingStats($startDate, $endDate, $providerId);
            });

            return view('provider.reports.bookings', compact('bookingStats', 'startDate', 'endDate'));
        } catch (\Exception $e) {
            Log::error('Provider bookings report failed: ' . $e->getMessage(), [
                'user_id' => $request->user()?->id,
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->with('error', 'حدث خطأ أثناء توليد تقرير الحجوزات. يرجى المحاولة لاحقاً.');
        }
    }


    public function trips(DashboardFilterRequest $request)
    {
        try {
            $user = $request->user();
            $providerId = $this->providerContext->providerUserIdFor($user);

            if (!$providerId) {
                abort(403, 'لا يوجد مزود خدمة مرتبط بحسابك');
            }

            [$startDate, $endDate] = $this->getDateRange($request->validated());

            $cacheKey = "provider_trips_report_{$providerId}_{$startDate->format('Y-m-d')}_{$endDate->format('Y-m-d')}";

            $data = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($startDate, $endDate, $providerId) {
                $tripPerformance = $this->reportService->getTripPerformanceReport($startDate, $endDate, $providerId);

                $summary = [
                    'total_trips' => count($tripPerformance),
                    'total_revenue' => collect($tripPerformance)->sum('revenue'),
                    'average_occupancy' => collect($tripPerformance)->avg('occupancy_rate') ?? 0,
                ];

                return compact('tripPerformance', 'summary');
            });

            return view('provider.reports.trips', array_merge($data, compact('startDate', 'endDate')));
        } catch (\Exception $e) {
            Log::error('Provider trips report failed: ' . $e->getMessage(), [
                'user_id' => $request->user()?->id,
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->with('error', 'حدث خطأ أثناء توليد تقرير الرحلات. يرجى المحاولة لاحقاً.');
        }
    }


    private function getDateRange(array $validated): array
    {
        $startDate = isset($validated['start_date'])
            ? Carbon::parse($validated['start_date'])->startOfDay()
            : Carbon::now()->subDays(30)->startOfDay();

        $endDate = isset($validated['end_date'])
            ? Carbon::parse($validated['end_date'])->endOfDay()
            : Carbon::now()->endOfDay();

        return [$startDate, $endDate];
    }
}
