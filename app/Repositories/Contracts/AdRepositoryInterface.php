<?php

namespace App\Repositories\Contracts;

use App\Models\Ad;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AdRepositoryInterface
{
    public function getAllAds(int $perPage, ?string $search, ?string $status): LengthAwarePaginator;

    public function findByUuid(string $uuid, array $relations = []): ?Ad;

    public function create(array $data): Ad;

    public function update(Ad $ad, array $data): bool;

    public function delete(Ad $ad): bool;

    public function getStatistics(): array;
}
