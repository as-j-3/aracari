<?php

namespace App\Repositories\Contracts;

use App\Constants\NumericConstants;
use App\Models\Coupon;
use Illuminate\Pagination\LengthAwarePaginator;

interface CouponRepositoryInterface
{
    public function search(array $filters, int $perPage = NumericConstants::DEFAULT_PER_PAGE): LengthAwarePaginator;

    public function findByUuid(string $uuid): ?Coupon;

    public function create(array $data, int $userId): Coupon;

    public function update(Coupon $coupon, array $data): array;

    public function delete(Coupon $coupon): bool;

    public function hasUsages(Coupon $coupon): bool;

    public function getCouponStatuses(): \Illuminate\Support\Collection;
}
