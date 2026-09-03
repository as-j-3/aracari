<?php
namespace App\Services\Admin;

use App\Models\Booking;
use App\Models\Trip;
use App\Constants\BookingStatus;
use App\Services\CurrencyService;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    private const CACHE_TTL = 300;
    private CurrencyService $currencyService;

    public function __construct(CurrencyService $currencyService)
    {
        $this->currencyService = $currencyService;
    }

    public function getDashboardData(): array
    {
        return [
            'stats'            => $this->getStats(),
            'upcoming_trips'   => $this->getUpcomingTrips(),
            'recent_bookings'  => $this->getRecentBookings(),
            //'base_currency'    => $this->currencyService->baseCurrency(),
        ];
    }

    public function getStats(): array
    {
        return Cache::remember('admin_dashboard_stats_v2', self::CACHE_TTL, function () {
            return [
                'total_trips'     => Trip::count(),
                'active_trips'    => Trip::where('departure_time', '>=', now())->count(),
                'todays_bookings' => Booking::whereDate('created_at', today())->count(),
                'total_revenue'   => (float) Booking::whereIn('booking_status_id', [BookingStatus::CONFIRMED])
                    ->sum('base_amount'),
            ];
        });
    }

    public function getUpcomingTrips()
    {
        return Cache::remember('admin_dashboard_upcoming_trips', 60, function () {
            return Trip::with(['departureCity', 'destinationCity', 'vehicle'])
                ->where('departure_time', '>=', now())
                ->orderBy('departure_time')
                ->limit(5)
                ->get()
                ->map(function ($trip) {
                    $bookedSeats = $trip->getBookedSeatIds() ?? [];
                    $totalSeats = $trip->vehicle?->seats()->count() ?? 0;

                    return [
                        'id'             => $trip->id,
                        'uuid'           => $trip->uuid,
                        'code'           => $trip->uuid,
                        'origin'         => $trip->departureCity?->name ?? '—',
                        'destination'    => $trip->destinationCity?->name ?? '—',
                        'departure_date' => $trip->departure_time?->format('Y-m-d'),
                        'departure_time' => $trip->departure_time?->format('H:i'),
                        'booked_seats'   => count($bookedSeats),
                        'total_seats'    => $totalSeats,
                    ];
                });
        });
    }

    public function getRecentBookings()
    {
        return Cache::remember('admin_dashboard_recent_bookings', 60, function () {
            return Booking::with(['user', 'status', 'tripReservations.trip'])
                ->latest()
                ->limit(5)
                ->get()
                ->map(function ($booking) {
                    $trip = $booking->tripReservations->first()?->trip;
                    $route = '—';
                    if ($trip) {
                        $route = ($trip->departureCity?->name ?? '—') . ' ← ' . ($trip->destinationCity?->name ?? '—');
                    }

                    $statusKey = match($booking->booking_status_id) {
                        BookingStatus::CONFIRMED => 'paid',
                        BookingStatus::PENDING => 'pending',
                        BookingStatus::CANCELLED, BookingStatus::CANCELLED_BY_USER, BookingStatus::CANCELLED_BY_ADMIN => 'cancelled',
                        default => 'pending'
                    };

                    $statusLabels = [
                        'paid'      => 'مدفوع',
                        'pending'   => 'قيد الانتظار',
                        'cancelled' => 'ملغي',
                    ];

                    return [
                        'user_name'    => $booking->user?->name ?? 'عميل',
                        'time_ago'     => $booking->created_at?->diffForHumans(),
                        'trip_route'   => $route,
                        'amount'       => (float) ($booking->base_amount ?? 0),
                        'status'       => $statusKey,
                        'status_label' => $statusLabels[$statusKey] ?? 'قيد الانتظار',
                    ];
                });
        });
    }
}