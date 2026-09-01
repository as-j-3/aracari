<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\User;
use App\Models\Trip;
use App\Models\AuditLog;
use App\Services\ReportOptimizationService;
use Carbon\Carbon;
use App\Constants\BookingStatus;
use App\Constants\TripStatus;

class ReportService
{
    public function __construct(
        private ReportOptimizationService $optimizationService
    ) {}

    public function getAuditLogs(array $filters, ?int $providerId = null): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = AuditLog::with(['performer:id,name'])->latest();

        if ($providerId) {
            $query->where('user_id', $providerId);
        } elseif (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['action'])) $query->where('action', 'like', '%' . $filters['action'] . '%');
        if (!empty($filters['date_from'])) $query->whereDate('created_at', '>=', $filters['date_from']);
        if (!empty($filters['date_to'])) $query->whereDate('created_at', '<=', $filters['date_to']);

        return $query->paginate(50);
    }

    public function getUsersForReports(?int $providerId = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = User::orderBy('name');

        if ($providerId) {
            $query->where('id', $providerId);
        }

        return $query->select('id', 'name')->limit(200)->get();
    }

    public function getBookingStats(Carbon $startDate, Carbon $endDate, ?int $providerId = null): array
    {
        return $this->optimizationService->getBookingStats($startDate, $endDate, $providerId);
    }

    public function getTripsReport(Carbon $startDate, Carbon $endDate, ?int $providerId = null, int $limit = 50): \Illuminate\Support\Collection
    {
        return $this->optimizationService->getTripsReport($startDate, $endDate, $providerId, $limit);
    }

    public function getPaymentMethodsReport(Carbon $startDate, Carbon $endDate, ?int $providerId = null): \Illuminate\Support\Collection
    {
        return $this->optimizationService->getPaymentMethodsReport($startDate, $endDate, $providerId);
    }

    public function getMonthlyRevenueReport(int $year, ?int $providerId = null): array
    {
        $query = Booking::selectRaw('
                MONTH(created_at) as month,
                SUM(base_amount) as total_revenue,
                COUNT(*) as total_bookings,
                AVG(base_amount) as average_booking_value
            ')
            ->whereYear('created_at', $year)
            ->where('booking_status_id', BookingStatus::CONFIRMED);

        if ($providerId) {
            $query->whereHas('trip.vehicle', function ($q) use ($providerId) {
                $q->where('user_id', $providerId);
            });
        }

        $yearlyData = $query->groupBy('month')->orderBy('month')->get();

        $monthlyData = [];
        for ($month = 1; $month <= 12; $month++) {
            $monthData = $yearlyData->firstWhere('month', $month);
            
            $monthlyData[] = [
                'month' => $month,
                'month_name' => Carbon::create($year, $month, 1)->format('F'),
                'total_revenue' => $monthData?->total_revenue ?? 0,
                'total_bookings' => $monthData?->total_bookings ?? 0,
                'average_booking_value' => $monthData?->average_booking_value ?? 0,
            ];
        }
        
        return $monthlyData;
    }

    public function getTripPerformanceReport(Carbon $startDate, Carbon $endDate, ?int $providerId = null): array
    {
        $trips = Trip::with([
                'departureCity:id,name', 
                'destinationCity:id,name', 
                'vehicle:id,seats_count', 
                'tripReservations.booking:id,base_amount,booking_status_id'
            ])
            ->withCount(['tripReservations as booked_seats' => function ($query) {
                $query->whereHas('booking', function ($q) {
                    $q->where('booking_status_id', BookingStatus::CONFIRMED);
                });
            }])
            ->whereBetween('departure_time', [$startDate, $endDate])
            ->where('trip_status_id', '!=', TripStatus::CANCELLED);

        if ($providerId) {
            $trips->whereHas('vehicle', function ($q) use ($providerId) {
                $q->where('user_id', $providerId);
            });
        }

        $trips = $trips->get();

        $performanceData = $trips->map(function ($trip) {
            $totalSeats = $trip->vehicle->seats_count ?? 0;
            $bookedSeats = $trip->booked_seats;
            $occupancyRate = $totalSeats > 0 ? ($bookedSeats / $totalSeats) * 100 : 0;

            $revenue = $trip->tripReservations
                ->filter(fn($r) => $r->booking?->booking_status_id == BookingStatus::CONFIRMED)
                ->sum(fn($r) => $r->booking->base_amount ?? 0);

            return [
                'trip_uuid' => $trip->uuid,
                'departure_city' => $trip->departureCity->name ?? 'Unknown',
                'destination_city' => $trip->destinationCity->name ?? 'Unknown',
                'departure_time' => $trip->departure_time,
                'total_seats' => $totalSeats,
                'booked_seats' => $bookedSeats,
                'occupancy_rate' => round($occupancyRate, 2),
                'revenue' => $revenue,
            ];
        });

        return $performanceData->toArray();
    }
}