<?php

namespace App\Repositories;

use App\Constants\NumericConstants;
use App\Models\DirectTransferPayment;
use App\Repositories\Contracts\DirectTransferPaymentRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class DirectTransferPaymentRepository implements DirectTransferPaymentRepositoryInterface
{
    protected DirectTransferPayment $model;

    public function __construct(DirectTransferPayment $model)
    {
        $this->model = $model;
    }

    public function search(array $filters, int $perPage = NumericConstants::DEFAULT_PER_PAGE): LengthAwarePaginator
    {
        $query = $this->model->newQuery()->with('paymentMethod');

        if (! empty($filters['search'])) {
            $escaped = str_replace(
                ['\\', '%', '_'],
                ['\\\\', '\\%', '\\_'],
                $filters['search']
            );

            $query->where(function (Builder $q) use ($escaped) {
                $q->where('name', 'like', "%{$escaped}%")
                  ->orWhere('phone_number', 'like', "%{$escaped}%")
                  ->orWhere('bank_name', 'like', "%{$escaped}%")
                  ->orWhere('bank_account_number', 'like', "%{$escaped}%");
            });
        }

        if (! empty($filters['payment_method_id'])) {
            $query->where('payment_method_id', $filters['payment_method_id']);
        }

        return $query->latest('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findByUuid(string $uuid): ?DirectTransferPayment
    {
        return $this->model->with('paymentMethod')
            ->where('uuid', $uuid)
            ->first();
    }

    public function create(array $data): DirectTransferPayment
    {
        $data['uuid'] = $data['uuid'] ?? (string) Str::uuid();

        return $this->model->create($data);
    }

    public function update(string $uuid, array $data): bool
    {
        $record = $this->findByUuid($uuid);

        return $record ? $record->update($data) : false;
    }

    public function delete(string $uuid): bool
    {
        $record = $this->findByUuid($uuid);

        return $record ? $record->delete() : false;
    }

    public function hasLinkedUserInfo(string $uuid): bool
    {
        $record = $this->findByUuid($uuid);

        return $record ? $record->userDirectPaymentInfos()->exists() : false;
    }
}