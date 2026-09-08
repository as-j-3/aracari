<?php

namespace App\Repositories;

use App\Constants\NumericConstants;
use App\Models\Coupon;
use App\Models\CouponStatus;
use App\Repositories\Contracts\CouponRepositoryInterface;
use App\Services\CouponService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class CouponRepository implements CouponRepositoryInterface
{
    public function __construct(
        private CouponService $couponService
    ) {}

    public function search(array $filters, int $perPage = NumericConstants::DEFAULT_PER_PAGE): LengthAwarePaginator
    {
        $query = Coupon::with('status');

        if (! empty($filters['search'])) {
            $escaped = str_replace(
                ['\\', '%', '_'],
                ['\\\\', '\\%', '\\_'],
                $filters['search']
            );

            $query->where(function (Builder $q) use ($escaped) {
                $q->where('code', 'like', "%{$escaped}%")
                  ->orWhere('name', 'like', "%{$escaped}%")
                  ->orWhere('description', 'like', "%{$escaped}%");
            });
        }

        if (! empty($filters['status_id'])) {
            $query->where('status_id', $filters['status_id']);
        }

        if (! empty($filters['discount_type'])) {
            $query->where('discount_type', $filters['discount_type']);
        }

        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== null) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        if (! empty($filters['date_from'])) {
            $query->where('start_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->where('end_date', '<=', $filters['date_to']);
        }

        return $query->latest('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findByUuid(string $uuid): ?Coupon
    {
        return Coupon::with('status')->where('uuid', $uuid)->first();
    }

    public function create(array $data, int $userId): Coupon
    {
        return $this->couponService->createCoupon($data, $userId);
    }

    public function update(Coupon $coupon, array $data): array
    {
        return $this->couponService->updateCoupon($coupon, $data);
    }

    public function delete(Coupon $coupon): bool
    {
        return $coupon->delete();
    }

    public function hasUsages(Coupon $coupon): bool
    {
        return $coupon->couponUsages()->exists();
    }

    public function getCouponStatuses(): \Illuminate\Support\Collection
    {
        return cache()->remember('coupon-statuses-list', 3600, function () {
            return CouponStatus::orderBy('name')->get(['id', 'name']);
        });
    }
}
