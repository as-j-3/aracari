<?php

namespace App\Http\Controllers\Web;

use App\Constants\TripStatus;
use App\Constants\UserTypes;
use App\Constants\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Trip\HomeSearchRequest;
use App\Models\Trip;
use App\Models\VehicleType;
use App\Models\City;
use App\Models\Currency;
use App\Models\User;
use App\Services\HomeService;
use App\Services\CurrencyService;
use App\Services\BookingService;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
class HomeController extends Controller
{

    public function __construct(
        private HomeService $homeService,
        private CurrencyService $currencyService,
        private BookingService $bookingService
    ) {}


    public function home(): View
    {
        try {
            $data = Cache::remember('home_page_data', now()->addMinutes(5), function () {
                return $this->homeService->getHomePageData();
            });
            
            $data['currencies'] = $this->getCurrenciesWithFallback();
            $data['baseCurrencyId'] = $this->currencyService->baseCurrencyId();
            $data['baseCurrency'] = $this->currencyService->baseCurrency();
            
        } catch (\Exception $e) {
            Log::error('Home page data loading failed: ' . $e->getMessage());
            
            $data = [
                'trips' => collect([]),
                'popular_cities' => collect([]),
                'stats' => [],
                'siteSettings' => [],
                'currencies' => [],
                'baseCurrencyId' => null,
                'baseCurrency' => null,
            ];
        }

        $data['featuredTrips'] = $data['trips'] ?? collect([]);
        $data['popularDestinations'] = $data['popular_cities'] ?? collect([]);
        $data['cities'] = \App\Models\City::orderBy('name')->get(['id', 'name', 'uuid']);
        
        $trustedProviders = collect();
        $activeStatus = \App\Models\UserStatus::where('status_code', 'active')->first();
        if ($activeStatus) {
            $trustedProviders = \App\Models\User::where('type_user_code', UserTypes::PROVIDER)
                ->where('status_id', $activeStatus->id)
                ->where('is_approved', true)
                ->whereHas('providerService', fn($q) => $q->where('status', 'active'))
                ->with('providerService:id,user_id,name,brand_photo')
                ->inRandomOrder()
                ->take(6)
                ->get(['id', 'name']);
        }
        $data['trustedProviders'] = $trustedProviders;
        
        if (empty($data['stats'])) {
            $data['stats'] = [
                'total_trips' => \App\Models\Trip::where('departure_time', '>=', now())->count(),
                'happy_travelers' => \App\Models\User::where('type_user_code', 1)->count(),
                'cities_covered' => \App\Models\City::count(),
                'total_vehicles' => \App\Models\Vehicle::where('status', 'active')->count(),
            ];
        }

        return view('home.index', $data);
    }


    public function search(HomeSearchRequest $request): View
    {
        $filters = $request->validated();
        
        $trips = $this->homeService->searchTrips($filters);
        
        if (!empty($filters['from_city'])) {
            $fromCity = City::where('uuid', $filters['from_city'])->first();
            $filters['from_city_name'] = $fromCity?->name;
        }
        if (!empty($filters['to_city'])) {
            $toCity = City::where('uuid', $filters['to_city'])->first();
            $filters['to_city_name'] = $toCity?->name;
        }

        $cities = City::orderBy('name')->get(['id', 'name', 'uuid']);

        $activeStatus = \App\Models\UserStatus::where('status_code', 'active')->first();
        $providers = collect();
        if ($activeStatus) {
            $providers = User::where('type_user_code', UserTypes::PROVIDER)
                ->where('status_id', $activeStatus->id)
                ->where('is_approved', true)
                ->whereHas('providerService', function ($q) {
                    $q->where('status', 'active');
                })
                ->whereHas('trips', function ($q) {
                    $q->where('is_public', true)
                      ->where('departure_time', '>=', now())
                      ->whereIn('trip_status_id', [
                          TripStatus::SCHEDULED,
                          TripStatus::IN_PROGRESS,
                      ]);
                })
                ->with('providerService:id,user_id,name,brand_photo')
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        $vehicleTypes = VehicleType::orderBy('type_name')->get();
        
        $currencies = $this->getCurrenciesWithFallback();
        $baseCurrencyId = $this->currencyService->baseCurrencyId();


        return view('home.search_results', compact(
            'trips',
            'vehicleTypes', 
            'filters',
            'currencies',
            'baseCurrencyId',
            'cities',
            'providers'
        ));
    }

    public function show(string $uuid)
    {
        $trip = $this->homeService->getTripDetails($uuid);

        if (!$trip) {
            abort(404, 'الرحلة غير موجودة');
        }

        if ($trip->departure_time <= now()) {
            abort(404, 'انتهت صلاحية هذه الرحلة');
        }

        if ($trip->ticket_limit && $trip->available_seats <= 0) {
            abort(404, 'لا توجد مقاعد متاحة في هذه الرحلة');
        }

        $similarTrips = $this->homeService->getSimilarTrips($trip);
        
        $currencies = $this->getCurrenciesWithFallback();
        $baseCurrencyId = $this->currencyService->baseCurrencyId();
        $baseCurrency = $this->currencyService->baseCurrency();
        $previousPassengers = auth()->check()
            ? $this->bookingService->getUserPreviousPassengers(auth()->id())
            : collect();

        $walletBalance = 0;
        $walletService = app(\App\Services\Wallet\WalletService::class);
        if (auth()->check() && $walletService->isWalletEnabled() && $walletService->isWalletPaymentEnabled()) {
            $currencyService = app(\App\Services\CurrencyService::class);
            $baseCurrencyId = $currencyService->baseCurrencyId();
            $walletAccount = $walletService->getOrCreateAccount(auth()->id(), $baseCurrencyId);
            $walletBalance = (float) $walletAccount->balance;
        }

        return view('home.trip_details', compact(
            'trip',
            'similarTrips',
            'currencies',
            'baseCurrencyId',
            'baseCurrency',
            'previousPassengers',
            'walletBalance'
        ));
    }


    private function getCurrenciesWithFallback(): array
    {
        try {
            return $this->currencyService->getAllCurrenciesWithRates();
        } catch (\Exception $e) {
            Log::warning('Currency loading failed, returning empty array', [
                'error' => $e->getMessage()
            ]);
            
            $baseCurrencyId = $this->currencyService->baseCurrencyId();
            $baseCurrency = Currency::find($baseCurrencyId);
            
            if ($baseCurrency) {
                return [[
                    'id' => $baseCurrency->id,
                    'code' => $baseCurrency->code,
                    'name' => $baseCurrency->name,
                    'exchange_rate' => 1.0,
                ]];
            }
            
            return [];
        }
    }
}