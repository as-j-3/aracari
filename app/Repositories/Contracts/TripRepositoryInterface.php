<?php

namespace App\Repositories\Contracts;

use App\Constants\NumericConstants;
use App\Models\Trip;
use Illuminate\Pagination\LengthAwarePaginator;


interface TripRepositoryInterface
{

    public function all(int $perPage = NumericConstants::DEFAULT_PER_PAGE): LengthAwarePaginator;


    public function findById(int $id): ?Trip;


    public function findByUuid(string $uuid, bool $includeDeleted = false, bool $publicOnly = false): ?Trip;


    public function search(array $filters, int $perPage = NumericConstants::DEFAULT_PER_PAGE): LengthAwarePaginator;

    public function getByUser(int $userId, int $perPage = NumericConstants::DEFAULT_PER_PAGE): LengthAwarePaginator;


    public function getAvailableTrips(array $filters, int $perPage = NumericConstants::DEFAULT_PER_PAGE): LengthAwarePaginator;


    public function getUpcomingTripsByCountry(string $isoCode, int $perPage = NumericConstants::DEFAULT_PER_PAGE): LengthAwarePaginator;


    public function create(array $data): Trip;


    public function update(int $id, array $data): bool;


    public function delete(int $id): bool;


    public function restore(Trip $trip): bool;
    
    public function checkAvailability(int $tripId): int;


    public function incrementSeats(int $tripId, int $seats = 1): bool;

 
    public function decrementSeats(int $tripId, int $seats = 1): bool;


    public function lockForUpdate(int $tripId): ?Trip;

    public function forceDelete(Trip $trip): ?bool;
}