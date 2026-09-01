<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Report\ReportFilterRequest;
use App\Services\ReportService;
use App\Services\CurrencyService;
use App\Services\ExchangeRateService;
use Carbon\Carbon;
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

    public function auditLogs(ReportFilterRequest $request)
    {
        if (!auth()->user()->hasPermission('reports:audit')) {
            abort(403);
        }

        try {
            $filters = $request->validated();
            
            $perPage = min($filters['per_page'] ?? 15, 100);
            
            $auditLogs = $this->reportService->getAuditLogs($filters, null);
            $users = $this->reportService->getUsersForReports(null);

            return view('admin.audit_logs.index', compact('auditLogs', 'users'));
        } catch (\Exception $e) {
            Log::error('Audit logs report failed: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء توليد سجلات التدقيق.');
        }
    }

    public function financialReports(ReportFilterRequest $request)
    {
        if (!auth()->user()->hasPermission('reports:financial')) {
            abort(403);
        }

        try {
            $validated = $request->validated();
            $startDate = $validated['start_date'] ?? Carbon::now()->startOfMonth();
            $endDate = $validated['end_date'] ?? Carbon::now()->endOfMonth();
            
            if ($startDate instanceof Carbon && $endDate instanceof Carbon && $startDate->diffInDays($endDate) > self::MAX_DAYS_RANGE) {
                return back()->with('error', 'لا يمكن عرض تقارير لأكثر من ' . self::MAX_DAYS_RANGE . ' يوماً');
            }
            
            if (($startDate instanceof Carbon && $startDate > Carbon::now()) || ($endDate instanceof Carbon && $endDate > Carbon::now())) {
                $startDate = Carbon::now()->startOfMonth();
                $endDate = Carbon::now()->endOfMonth();
            }

            Log::info('Financial report accessed', [
                'user_id' => auth()->id(),
                'ip' => request()->ip(),
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]);

            $baseCurrency = $this->currencyService->baseCurrency();
            $currencies = $this->currencyService->getAllCurrenciesWithRates();       
            $selectedCurrencyId = $validated['currency_id'] ?? $baseCurrency->id;
            $selectedCurrency = \App\Models\Currency::find($selectedCurrencyId) ?? $baseCurrency;
            $exchangeRate = $this->exchangeRateService->getRate($selectedCurrencyId, $baseCurrency->id);
            
            $bookingStats = $this->reportService->getBookingStats($startDate, $endDate, null);
            $tripsReport = $this->reportService->getTripsReport($startDate, $endDate, null);
            $tripsReport = collect($tripsReport)->take(10);
            $paymentMethodsReport = $this->reportService->getPaymentMethodsReport($startDate, $endDate, null);
            
            return view('admin.reports', compact(
                'bookingStats', 'tripsReport', 'paymentMethodsReport',
                'startDate', 'endDate', 'baseCurrency', 'currencies',
                'selectedCurrency', 'selectedCurrencyId', 'exchangeRate'
            ));
        } catch (\Exception $e) {
            Log::error('Financial reports failed: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء توليد التقارير المالية.');
        }
    }

    public function monthlyRevenue(ReportFilterRequest $request)
    {
        if (!auth()->user()->hasPermission('reports:financial')) {
            abort(403);
        }

        try {
            $validated = $request->validated();
            $year = $validated['year'] ?? Carbon::now()->year;
            $monthlyData = $this->reportService->getMonthlyRevenueReport($year, null);
            return view('admin.monthly_revenue', compact('monthlyData', 'year'));
        } catch (\Exception $e) {
            Log::error('Monthly revenue report failed: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء توليد تقرير الإيرادات.');
        }
    }

    public function tripPerformance(ReportFilterRequest $request)
    {
        if (!auth()->user()->hasPermission('reports:financial')) {
            abort(403);
        }

        try {
            $validated = $request->validated();
            $startDate = $validated['start_date'] ?? Carbon::now()->startOfMonth();
            $endDate = $validated['end_date'] ?? Carbon::now()->endOfMonth();
            $performanceData = $this->reportService->getTripPerformanceReport($startDate, $endDate, null);
            return view('admin.trip_performance', compact('performanceData', 'startDate', 'endDate'));
        } catch (\Exception $e) {
            Log::error('Trip performance report failed: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء توليد تقرير الأداء.');
        }
    }
}