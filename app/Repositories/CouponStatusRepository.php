<?php

namespace App\Repositories;

use App\Constants\NumericConstants;
use App\Models\CouponStatus;
use App\Repositories\Contracts\CouponStatusRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class CouponStatusRepository implements CouponStatusRepositoryInterface
{
    protected CouponStatus $model;

    public function __construct(CouponStatus $model)
    {
        $this->model = $model;
    }

    public function search(array $filters, int $perPage = NumericConstants::DEFAULT_PER_PAGE): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        if (! empty($filters['search'])) {
            $escaped = str_replace(
                ['\\', '%', '_'],
                ['\\\\', '\\%', '\\_'],
                $filters['search']
            );

            $query->where(fn (Builder $q) => $q->where('name', 'like', "%{$escaped}%"));
        }

        return $query->latest('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findById(int $id): ?CouponStatus
    {
        return $this->model->find($id);
    }

    public function create(array $data): CouponStatus
    {
        $status = $this->model->create($data);

        $this->clearStatusCache();

        return $status;
    }

    public function update(int $id, array $data): bool
    {
        $status = $this->findById($id);

        if (! $status) {
            return false;
        }

        $result = $status->update($data);

        if ($result) {
            $this->clearStatusCache();
        }

        return $result;
    }

    public function delete(int $id): bool
    {
        $status = $this->findById($id);

        if (! $status) {
            return false;
        }

        $result = $status->delete();

        if ($result) {
            $this->clearStatusCache();
        }

        return $result;
    }

    public function hasCoupons(int $id): bool
    {
        $status = $this->findById($id);

        return $status ? $status->coupons()->exists() : false;
    }

    private function clearStatusCache(): void
    {
        cache()->forget('coupon-statuses-list');
    }
}