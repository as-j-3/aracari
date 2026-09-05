<?php

namespace App\Repositories;

use App\Constants\BookingStatus as BookingStatusConst;
use App\Constants\NumericConstants;
use App\Constants\WeekDays;
use App\Constants\TripStatus;
use App\Models\Trip;
use App\Repositories\Contracts\TripRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class TripRepository implements TripRepositoryInterface
{
    protected Trip $trip;

    public function __construct(Trip $trip)
    {
        $this->trip = $trip;
    }

    public function all(int $perPage = NumericConstants::DEFAULT_PER_PAGE): LengthAwarePaginator
    {
        return $this->baseQuery()
            ->orderBy('departure_time')
            ->paginate($perPage);
    }

    public function findById(int $id): ?Trip
    {
        return $this->baseQuery()->find($id);
    }

    public function findByUuid(string $uuid, bool $includeDeleted = false, bool $publicOnly = false): ?Trip
    {
        $query = $this->baseQuery();

        if ($includeDeleted) {
            $query = $this->trip->withTrashed();
        }

        if ($publicOnly) {
            $query->where('is_public', true);
        }

        return $query->where('uuid', $uuid)->first();
    }

    public function create(array $data): Trip
    {
        return $this->trip->create($data);
    }

    public function update(int $id, array $data): bool
    {
        $trip = $this->findById($id);

        return $trip ? $trip->update($data) : false;
    }

    public function delete(int $id): bool
    {
        $trip = $this->findById($id);

        return $trip ? $trip->delete() : false;
    }

    public function restore(Trip $trip): bool
    {
        return $trip->restore();
    }

    public function search(array $filters, int $perPage = NumericConstants::DEFAULT_PER_PAGE): LengthAwarePaginator
    {
        $query = $this->baseQuery();

        if (isset($filters['publicOnly']) && $filters['publicOnly']) {
            $query->where('is_public', true);
        }

        // Search by keyword (trip UUID, departure/destination city name, pickup location, station names, provider name, vehicle plate/name/model)
        if (!empty($filters['search'])) {
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $filters['search']);
            $query->where(function ($q) use ($escaped) {
                $q->where('trips.uuid', 'like', "%{$escaped}%")
                  ->orWhere('trips.pickup_location', 'like', "%{$escaped}%")
                  ->orWhereHas('departureCity', function ($cq) use ($escaped) {
                      $cq->where('name', 'like', "%{$escaped}%");
                  })
                  ->orWhereHas('destinationCity', function ($cq) use ($escaped) {
                      $cq->where('name', 'like', "%{$escaped}%");
                  })
                  ->orWhereHas('departureStation', function ($sq) use ($escaped) {
                      $sq->where('name', 'like', "%{$escaped}%");
                  })
                  ->orWhereHas('arrivalStation', function ($sq) use ($escaped) {
                      $sq->where('name', 'like', "%{$escaped}%");
                  })
                  ->orWhereHas('user', function ($uq) use ($escaped) {
                      $uq->where('name', 'like', "%{$escaped}%")
                         ->orWhereHas('providerService', function ($psq) use ($escaped) {
                             $psq->where('name', 'like', "%{$escaped}%");
                         });
                  })
                  ->orWhereHas('vehicle', function ($vq) use ($escaped) {
                      $vq->where('name', 'like', "%{$escaped}%")
                         ->orWhere('model', 'like', "%{$escaped}%");
                  });
            });
        }

        // Filter by status (ID or code/name)
        if (!empty($filters['status'])) {
            if (is_numeric($filters['status'])) {
                $query->where('trip_status_id', $filters['status']);
            } else {
                $query->whereHas('tripStatus', function ($sq) use ($filters) {
                    $sq->where('status_code', $filters['status'])
                       ->orWhere('status_name', $filters['status']);
                });
            }
        }

        // Filter by city (matches departure or destination by ID or Name)
        if (!empty($filters['city'])) {
            if (is_numeric($filters['city'])) {
                $query->where(function ($q) use ($filters) {
                    $q->where('departure_city', $filters['city'])
                      ->orWhere('destination_city', $filters['city']);
                });
            } else {
                $cityName = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $filters['city']);
                $query->where(function ($q) use ($cityName) {
                    $q->whereHas('departureCity', function ($cq) use ($cityName) {
                        $cq->where('name', 'like', "%{$cityName}%");
                    })->orWhereHas('destinationCity', function ($cq) use ($cityName) {
                        $cq->where('name', 'like', "%{$cityName}%");
                    });
                });
            }
        }

        // Filter by departure date
        if (!empty($filters['date'])) {
            $query->whereDate('departure_time', $filters['date']);
        }

        return $query->orderBy('departure_time', 'desc')->paginate($perPage);
    }

    public function getByUser(int $userId, int $perPage = NumericConstants::DEFAULT_PER_PAGE): LengthAwarePaginator
    {
        return $this->baseQuery()
            ->where('created_by_user', $userId)
            ->orderBy('departure_time')
            ->paginate($perPage);
    }

    public function getAvailableTrips(array $filters, int $perPage = NumericConstants::DEFAULT_PER_PAGE): LengthAwarePaginator
    {
        $totalPassengers = ($filters['adults'] ?? 1) + ($filters['children'] ?? 0);

        $query = $this->baseQuery()
            ->join('vehicles', 'trips.vehicle_id', '=', 'vehicles.id')
            ->where('departure_time', '>', now())
            ->where('is_public', true)
            ->whereIn('trip_status_id', [TripStatus::SCHEDULED, TripStatus::IN_PROGRESS])
            ->where(function ($q) {
                $q->where('available_seats', '>', 0)
                    ->orWhereNull('ticket_limit');
            });

        if (!empty($filters['from_city'])) {
            $query->where('departure_city', $filters['from_city']);
        }

        if (!empty($filters['to_city'])) {
            $query->where('destination_city', $filters['to_city']);
        }

        if (!empty($filters['departure_date'])) {
            $date = Carbon::parse($filters['departure_date']);
            $dayName = WeekDays::toDatabaseValue(WeekDays::fromCarbonDay($date->dayOfWeek));

            $query->where(function ($inner) use ($date, $dayName) {
                $inner->whereDate('departure_time', $date);

                if ($dayName) {
                    $inner->orWhereHas('repeats', function ($repeatQuery) use ($date, $dayName) {
                        $repeatQuery->where('status', 'active')
                            ->whereDate('start_date', '<=', $date)
                            ->whereDate('end_date', '>=', $date)
                            ->whereHas('repeatedDays', function ($dayQuery) use ($dayName) {
                                $dayQuery->where('day_of_week', $dayName);
                            });
                    });
                }
            });
        }

        if (!empty($filters['min_price'])) {
            $query->where('price_for_adult', '>=', $filters['min_price']);
        }

        if (!empty($filters['max_price'])) {
            $query->where('price_for_adult', '<=', $filters['max_price']);
        }

        if (!empty($filters['type_of_trip_id'])) {
            $query->where('type_of_trip_id', $filters['type_of_trip_id']);
        }

        if (!empty($filters['level_trip_id'])) {
            $query->where('level_trip_id', $filters['level_trip_id']);
        }

        if (!empty($filters['provider_id'])) {
            $query->where('trips.created_by_user', $filters['provider_id']);
        }

        if (!empty($filters['departure_location'])) {
            $query->where('trips.pickup_location', 'like', '%' . $filters['departure_location'] . '%');
        }

        if (!empty($filters['arrival_location'])) {
            $query->where('trips.arrival_location', 'like', '%' . $filters['arrival_location'] . '%');
        }

        if ($totalPassengers > 0) {
            $query->hasAvailableSeats($totalPassengers);
        }

        return $query
            ->select('trips.*')
            ->orderBy('departure_time')
            ->paginate($perPage);
    }

    public function checkAvailability(int $tripId): int
    {
        $trip = $this->findById($tripId);

        if (!$trip || !$trip->vehicle) {
            return 0;
        }

        $totalSeats = $trip->vehicle->seats()->count();
        $bookedSeatsCount = count($trip->getBookedSeatIds());

        return $totalSeats - $bookedSeatsCount;
    }

    public function incrementSeats(int $tripId, int $seats = 1): bool
    {
        return DB::transaction(function () use ($tripId, $seats) {
            $trip = $this->trip
                ->with('vehicle')
                ->where('id', $tripId)
                ->lockForUpdate()
                ->first();

            if (!$trip) {
                return false;
            }

            if (!$trip->vehicle) {
                return false;
            }

            $maxSeats = (int) $trip->vehicle->seats()->count();
            $bookedSeats = count($trip->getBookedSeatIds());

            return ($bookedSeats + $seats) <= $maxSeats;
        });
    }

    public function lockForUpdate(int $tripId): ?Trip
    {
        return $this->trip
            ->with(['vehicle', 'confirmedTripReservations'])
            ->where('id', $tripId)
            ->lockForUpdate()
            ->first();
    }

    public function decrementSeats(int $tripId, int $seats = 1): bool
    {
        return DB::transaction(function () use ($tripId, $seats) {
            $trip = \App\Models\Trip::with('vehicle')->where('id', $tripId)->lockForUpdate()->first();

            if (!$trip || !$trip->vehicle) {
                return false;
            }

            $totalSeats = $trip->vehicle->seats()->count();
            $bookedSeats = count($trip->getBookedSeatIds());
            $availableSeats = $totalSeats - $bookedSeats;

            if ($availableSeats < $seats) {
                return false;
            }

            return true;
        });
    }

    public function getUpcomingTripsByCountry(string $isoCode, int $perPage = NumericConstants::DEFAULT_PER_PAGE): LengthAwarePaginator
    {
        $cityIds = \App\Models\City::whereHas('country', function ($q) use ($isoCode) {
            $q->where('iso_code', $isoCode);
        })->pluck('id');

        if ($cityIds->isEmpty()) {
            return new LengthAwarePaginator([], 0, $perPage, 1);
        }

        $query = $this->trip->withTrashed()
            ->with([
                'user',
                'vehicle',
                'policies',
                'typeOfTrip',
                'levelTrip',
                'tripStatus',
                'departureCity.country',
                'destinationCity.country',
                'tripAmenities.amenity',
                'repeats.repeatedDays',
                'departureStation',
                'arrivalStation',
            ])
            ->join('vehicles', 'trips.vehicle_id', '=', 'vehicles.id')
            ->whereIn('departure_city', $cityIds)
            ->where('departure_time', '>', now())
            ->where('is_public', true)
            ->whereIn('trip_status_id', [TripStatus::SCHEDULED, TripStatus::IN_PROGRESS])
            ->where(function ($q) {
                $q->where('available_seats', '>', 0)
                    ->orWhereNull('ticket_limit');
            })
            ->hasAvailableSeats(1)
            ->select('trips.*')
            ->orderBy('departure_time');

        return $query->paginate($perPage);
    }

    protected function baseQuery()
    {
        return $this->trip->withTrashed()->with([
            'user',
            'vehicle',
            'policies',
            'typeOfTrip',
            'levelTrip',
            'tripStatus',
            'departureCity',
            'destinationCity',
            'tripAmenities.amenity',
            'repeats.repeatedDays',
        ]);
    }

    public function forceDelete(Trip $trip): ?bool
    {
        return $trip->forceDelete();
    }
}
