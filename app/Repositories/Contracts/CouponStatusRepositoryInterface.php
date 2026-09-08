<?php

namespace App\Repositories\Contracts;

use App\Constants\NumericConstants;
use App\Models\CouponStatus;
use Illuminate\Pagination\LengthAwarePaginator;

interface CouponStatusRepositoryInterface
{
    public function search(array $filters, int $perPage = NumericConstants::DEFAULT_PER_PAGE): LengthAwarePaginator;

    public function findById(int $id): ?CouponStatus;

    public function create(array $data): CouponStatus;

    public function update(int $id, array $data): bool;

    public function delete(int $id): bool;

    public function hasCoupons(int $id): bool;
}