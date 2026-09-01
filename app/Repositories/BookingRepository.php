<?php

namespace App\Repositories;

use App\Models\Booking;
use App\Repositories\Contracts\BookingRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BookingRepository implements BookingRepositoryInterface
{
    public function findByUuid(string $uuid, array $relations = []): ?Booking
    {
        return Booking::where('uuid', $uuid)
            ->with($relations)
            ->first();
    }

    public function getUserBookings(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return Booking::where('user_id', $userId)
            ->with([
                'status:id,status_name',
                'payment:id,payment_method_id,payed_amount',
                'tripReservations.trip:id,uuid,departure_city,destination_city,departure_time',
                'tripReservations.tickets.passenger:id,full_name,birthday',
                'tripReservations.tickets.seat:id,seat_name',
                'taxes.tax:id,name,type,rate',
                'tripReservations.trip.departureCity:id,name',
                'tripReservations.trip.destinationCity:id,name',
                'commission:id,commission_type,calculation_level,description',
            ])
            ->latest()
            ->paginate($perPage);
    }

    public function getAllBookings(int $perPage = 15, ?string $statusFilter = null, ?string $search = null): LengthAwarePaginator
    {
        $query = Booking::with([
            'user:id,name,email,phone_number',
            'currency:id,code,symbol',
            'status:id,status_name',
            'payment.paymentMethod:id,method_name',
            'tripReservations.trip.departureCity:id,name,country_id',
            'tripReservations.trip.destinationCity:id,name,country_id',
            'tripReservations.trip.departureStation:id,name',
            'tripReservations.trip.arrivalStation:id,name',
            'tripReservations.trip.vehicle:id,name,model',
            'tripReservations.trip.vehicle.vehicleType:id,type_name',
            'tripReservations.tickets.passenger:id,full_name,birthday',
            'tripReservations.tickets.seat:id,seat_name',
            'tripReservations.tickets' => function ($q) {
                $q->select('id', 'trip_reservation_id', 'passenger_id', 'seat_id', 'ticket_number', 'price_at_booking', 'status', 'cancelled_at');
            },
            'taxes:id,booking_id,tax_id,tax_amount',
            'commission:id,commission_type,calculation_level,description',
        ]);

        if ($statusFilter !== null && $statusFilter !== '') {
            $allowedStatuses = [
                'confirmed' => [\App\Constants\BookingStatus::CONFIRMED],
                'pending'   => [\App\Constants\BookingStatus::PENDING],
                'cancelled' => \App\Constants\BookingStatus::getCancelledStatuses(),
                'completed' => [\App\Constants\BookingStatus::COMPLETED],
            ];
            if (array_key_exists($statusFilter, $allowedStatuses)) {
                $query->whereIn('booking_status_id', $allowedStatuses[$statusFilter]);
            }
        }

        if ($search !== null && $search !== '') {
            $safeSearch = str_replace(['%', '_'], ['\\%', '\\_'], $search);
            $query->where(function ($q) use ($safeSearch) {
                $q->where('bookings.uuid', 'like', "%{$safeSearch}%")
                  ->orWhereHas('user', function ($uq) use ($safeSearch) {
                      $uq->where('name', 'like', "%{$safeSearch}%");
                  });
            });
        }

        return $query->latest()->paginate($perPage);
    }

    public function create(array $data): Booking
    {
        return Booking::create($data);
    }

    public function update(Booking $booking, array $data): bool
    {
        return $booking->update($data);
    }
}