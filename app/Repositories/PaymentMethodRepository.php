<?php

namespace App\Repositories;

use App\Constants\NumericConstants;
use App\Constants\PaymentMethod as PaymentMethodConstants;
use App\Models\PaymentMethod;
use App\Repositories\Contracts\PaymentMethodRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class PaymentMethodRepository implements PaymentMethodRepositoryInterface
{
    protected PaymentMethod $paymentMethod;

    public function __construct(PaymentMethod $paymentMethod)
    {
        $this->paymentMethod = $paymentMethod;
    }

    public function search(array $filters, int $perPage = NumericConstants::DEFAULT_PER_PAGE): LengthAwarePaginator
    {
        $query = $this->paymentMethod->newQuery();

        if (! empty($filters['search'])) {
            $escaped = str_replace(
                ['\\', '%', '_'],
                ['\\\\', '\\%', '\\_'],
                $filters['search']
            );

            $query->where(fn (Builder $q) => $q->where('method_name', 'like', "%{$escaped}%"));
        }

        return $query->latest('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findById(int $id): ?PaymentMethod
    {
        return $this->paymentMethod->find($id);
    }

    public function create(array $data): PaymentMethod
    {
        return $this->paymentMethod->create($data);
    }

    public function update(int $id, array $data): bool
    {
        $method = $this->findById($id);

        return $method ? $method->update($data) : false;
    }

    public function delete(int $id): bool
    {
        $method = $this->findById($id);

        return $method ? $method->delete() : false;
    }

    public function isSystemMethod(int $id): bool
    {
        return in_array($id, [
            PaymentMethodConstants::WALLET,
            PaymentMethodConstants::CREDIT_CARD,
            PaymentMethodConstants::BANK_TRANSFER,
            PaymentMethodConstants::CASH,
        ]);
    }

    public function hasBookingPayments(int $id): bool
    {
        $method = $this->findById($id);

        return $method ? $method->bookingPayments()->exists() : false;
    }
}