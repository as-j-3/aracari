<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Trip;
use App\Constants\BookingStatus;
use App\Services\CurrencyService;
use Illuminate\View\View;
use Illuminate\Support\Facades\Cache;
use App\Services\Admin\DashboardService;
use Symfony\Component\HttpFoundation\JsonResponse;

class DashboardController extends Controller
{
    private DashboardService $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
        $this->middleware('require.admin');
    }
    public function index(): JsonResponse
    {
        $data = $this->dashboardService->getDashboardData();

        return response()->json([
            'success' => true,
            'message' => 'تم جلب بيانات لوحة التحكم بنجاح',
            'data'    => $data,
        ], 200);
    }
}