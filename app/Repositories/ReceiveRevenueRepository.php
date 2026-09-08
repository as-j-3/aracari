<?php

namespace App\Repositories;

use App\Constants\NumericConstants;
use App\Models\ReceiveRevenueMethod;
use App\Repositories\Contracts\ReceiveRevenueRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class ReceiveRevenueRepository implements ReceiveRevenueRepositoryInterface
{
    protected ReceiveRevenueMethod $model;

    public function __construct(ReceiveRevenueMethod $model)
    {
        $this->model = $model;
    }

    public function search(array $filters, int $perPage = NumericConstants::DEFAULT_PER_PAGE): LengthAwarePaginator
    {
        $query = $this->model->newQuery()->with(['digitalPocket', 'provider']);

        return $query->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findById(int $id): ?ReceiveRevenueMethod
    {
        return $this->model->with(['digitalPocket', 'provider'])->find($id);
    }

    public function create(array $data): ReceiveRevenueMethod
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): bool
    {
        $record = $this->findById($id);

        return $record ? $record->update($data) : false;
    }

    public function delete(int $id): bool
    {
        $record = $this->findById($id);

        return $record ? $record->delete() : false;
    }

    public function isDuplicate(array $data, ?int $excludeId = null): bool
    {
        $query = $this->model->newQuery()
            ->where('digital_pocket_id', $data['digital_pocket_id'])
            ->where('provider_id', $data['provider_id'])
            ->where('account_number', $data['account_number']);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
