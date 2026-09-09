<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Report\ReportFilterRequest;
use App\Http\Resources\Admin\AuditLogResource;
use App\Models\Currency;
use App\Services\CurrencyService;
use App\Services\ExchangeRateService;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ReportController extends Controller
{
    private const MAX_DAYS_RANGE = 90;

    private const CACHE_TTL = 600;

    public function __construct(
        private ReportService $reportService,
        private CurrencyService $currencyService,
        private ExchangeRateService $exchangeRateService
    ) {
        $this->middleware('require.admin');
    }

    public function auditLogs(ReportFilterRequest $request): JsonResponse
    {
        if (! auth()->user()->hasPermission('reports:audit')) {
            return $this->error('غير مصرح لك', null, 403);
        }

        try {
            /*
            // الكود السابق (Web View):
            $filters = $request->validated();

            $perPage = min($filters['per_page'] ?? 15, 100);

            $auditLogs = $this->reportService->getAuditLogs($filters, null);
            $users = $this->reportService->getUsersForReports(null);

            return view('admin.audit_logs.index', compact('auditLogs', 'users'));
            */

            $filters = $request->validated();

            $auditLogs = $this->reportService->getAuditLogs($filters, null);
            $users = $this->reportService->getUsersForReports(null);

            return $this->success([
                'logs' => AuditLogResource::collection($auditLogs),
                'users' => $users,
            ], 'تم جلب سجلات التدقيق بنجاح');
        } catch (\Exception $e) {
            Log::error('Audit logs report failed: '.$e->getMessage());

            return $this->error('حدث خطأ أثناء توليد سجلات التدقيق.');
        }
    }

    public function financialReports(ReportFilterRequest $request): JsonResponse
    {
        if (! auth()->user()->hasPermission('reports:financial')) {
            return $this->error('غير مصرح لك', null, 403);
        }

        try {
            $validated = $request->validated();

            /*
            |--------------------------------------------------------------------------
            | تحديد الفترة الزمنية
            |--------------------------------------------------------------------------
            */

            $startDate = ! empty($validated['start_date'])
                ? Carbon::parse($validated['start_date'])->startOfDay()
                : Carbon::now()->startOfMonth();

            $endDate = ! empty($validated['end_date'])
                ? Carbon::parse($validated['end_date'])->endOfDay()
                : Carbon::now()->endOfMonth();

            /*
            |--------------------------------------------------------------------------
            | التحقق من الفترة
            |--------------------------------------------------------------------------
            */

            if ($startDate->diffInDays($endDate) > self::MAX_DAYS_RANGE) {
                return $this->error(
                    'لا يمكن عرض تقارير لأكثر من '.self::MAX_DAYS_RANGE.' يوماً'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | منع التواريخ المستقبلية
            |--------------------------------------------------------------------------
            */

            if ($startDate > Carbon::now() || $endDate > Carbon::now()) {
                $startDate = Carbon::now()->startOfMonth();
                $endDate = Carbon::now()->endOfMonth();
            }

            /*
            |--------------------------------------------------------------------------
            | تسجيل الوصول للتقرير
            |--------------------------------------------------------------------------
            */

            Log::info('Financial report accessed', [
                'user_id' => auth()->id(),
                'ip' => request()->ip(),
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]);

            /*
            |--------------------------------------------------------------------------
            | العملات
            |--------------------------------------------------------------------------
            */

            $baseCurrency = $this->currencyService->baseCurrency();

            $currencies = $this->currencyService->getAllCurrenciesWithRates();

            $selectedCurrencyId = $validated['currency_id'] ?? $baseCurrency->id;

            $selectedCurrency = Currency::find($selectedCurrencyId)
                ?? $baseCurrency;

            $exchangeRate = $this->exchangeRateService->getRate(
                $selectedCurrencyId,
                $baseCurrency->id
            );

            /*
            |--------------------------------------------------------------------------
            | إحصائيات الحجوزات
            |--------------------------------------------------------------------------
            */

            $bookingStats = $this->reportService->getBookingStats(
                $startDate,
                $endDate,
                null
            );

            /*
            |--------------------------------------------------------------------------
            | تقرير الرحلات
            |--------------------------------------------------------------------------
            */

            $tripsReport = $this->reportService->getTripsReport(
                $startDate,
                $endDate,
                null
            );

            $tripsReport = collect($tripsReport)->take(10);

            /*
            |--------------------------------------------------------------------------
            | تقرير طرق الدفع
            |--------------------------------------------------------------------------
            */

            $paymentMethodsReport = $this->reportService->getPaymentMethodsReport(
                $startDate,
                $endDate,
                null
            );

            /*
            |--------------------------------------------------------------------------
            | آخر الحركات المحاسبية
            |--------------------------------------------------------------------------
            */

            $latestTransactions = $this->reportService->getLatestTransactions(10);

            /*
            |--------------------------------------------------------------------------
            | الاستجابة
            |--------------------------------------------------------------------------
            */

            return $this->success([
                'booking_stats' => $bookingStats,

                'trips_report' => $tripsReport,

                'payment_methods_report' => $paymentMethodsReport,

                'latest_transactions' => $latestTransactions,

                'start_date' => $startDate->toDateString(),

                'end_date' => $endDate->toDateString(),

                'base_currency' => $baseCurrency,

                'currencies' => $currencies,

                'selected_currency' => $selectedCurrency,

                'selected_currency_id' => $selectedCurrencyId,

                'exchange_rate' => $exchangeRate,
            ], 'تم توليد التقرير المالي بنجاح');

        } catch (\Exception $e) {

            Log::error('Financial reports failed: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(
                'حدث خطأ أثناء توليد التقارير المالية.',
                null,
                500
            );
        }
    }

    public function monthlyRevenue(ReportFilterRequest $request): JsonResponse
    {
        if (! auth()->user()->hasPermission('reports:financial')) {
            return $this->error('غير مصرح لك', null, 403);
        }

        try {
            /*
            // الكود السابق (Web View):
            $validated = $request->validated();
            $year = $validated['year'] ?? Carbon::now()->year;
            $monthlyData = $this->reportService->getMonthlyRevenueReport($year, null);
            return view('admin.monthly_revenue', compact('monthlyData', 'year'));
            */

            $validated = $request->validated();
            $year = $validated['year'] ?? Carbon::now()->year;
            $monthlyData = $this->reportService->getMonthlyRevenueReport($year, null);

            return $this->success([
                'year' => $year,
                'monthly_data' => $monthlyData,
            ], 'تم توليد تقرير الإيرادات الشهرية بنجاح');
        } catch (\Exception $e) {
            Log::error('Monthly revenue report failed: '.$e->getMessage());

            return $this->error('حدث خطأ أثناء توليد تقرير الإيرادات.');
        }
    }

    public function tripPerformance(ReportFilterRequest $request): JsonResponse
    {
        if (! auth()->user()->hasPermission('reports:financial')) {
            return $this->error('غير مصرح لك', null, 403);
        }

        try {
            /*
            // الكود السابق (Web View):
            $validated = $request->validated();
            $startDate = $validated['start_date'] ?? Carbon::now()->startOfMonth();
            $endDate = $validated['end_date'] ?? Carbon::now()->endOfMonth();
            $performanceData = $this->reportService->getTripPerformanceReport($startDate, $endDate, null);
            return view('admin.trip_performance', compact('performanceData', 'startDate', 'endDate'));
            */

            $validated = $request->validated();
            $startDate = $validated['start_date'] ?? Carbon::now()->startOfMonth();
            $endDate = $validated['end_date'] ?? Carbon::now()->endOfMonth();
            $performanceData = $this->reportService->getTripPerformanceReport($startDate, $endDate, null);

            return $this->success([
                'performance_data' => $performanceData,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ], 'تم توليد تقرير أداء الرحلات بنجاح');
        } catch (\Exception $e) {
            Log::error('Trip performance report failed: '.$e->getMessage());

            return $this->error('حدث خطأ أثناء توليد تقرير الأداء.');
        }
    }
}
