<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Trip\IndexTripRequest;
use App\Services\TripService;
use App\Services\CurrencyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;


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

    public function index(IndexTripRequest $request): View
    {
        $filters = $request->only(['search', 'status', 'city', 'date']);
        $perPage = $request->get('per_page', 15);
        $trips = $this->tripService->getAllTrips($filters, $perPage);
        $trips->load(['vehicle.user.providerService', 'departureCity', 'destinationCity', 'coupons']);
        $baseCurrency = $this->currencyService->baseCurrency();

        return view('admin.trips.index', compact('trips', 'baseCurrency'));
    }


    public function show(Request $request, string $uuid): View|JsonResponse
    {
        $trip = $this->tripService->getTripByUuid($uuid);
        if (!$trip) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'الرحلة التي تحاول الوصول لها غير موجودة'], 404);
            }
            return redirect()->route('admin.trips.index')->with('error', 'الرحلة التي تحاول الوصول لها غير موجودة');
        }
        $baseCurrency = $this->currencyService->baseCurrency();

        return view('admin.trips.show', compact('trip', 'baseCurrency'));
    }
}
