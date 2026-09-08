<?php

namespace App\Repositories\Contracts;

use App\Constants\NumericConstants;
use App\Models\ReceiveRevenueMethod;
use Illuminate\Pagination\LengthAwarePaginator;

interface ReceiveRevenueRepositoryInterface
{
    public function search(array $filters, int $perPage = NumericConstants::DEFAULT_PER_PAGE): LengthAwarePaginator;

    public function findById(int $id): ?ReceiveRevenueMethod;

    public function create(array $data): ReceiveRevenueMethod;

    public function update(int $id, array $data): bool;

    public function delete(int $id): bool;

    public function isDuplicate(array $data, ?int $excludeId = null): bool;
}
