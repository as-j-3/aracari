<?php

namespace App\Repositories\Contracts;

use App\Models\Booking;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface BookingRepositoryInterface
{

    public function findByUuid(string $uuid, array $relations = []): ?Booking;

    public function getUserBookings(int $userId, int $perPage = 15): LengthAwarePaginator;

    public function getAllBookings(int $perPage = 15, ?string $statusFilter = null, ?string $search = null): LengthAwarePaginator;

    public function create(array $data): Booking;

    public function update(Booking $booking, array $data): bool;
}