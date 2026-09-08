<?php

namespace App\Repositories;

use App\Constants\NumericConstants;
use App\Models\Currency;
use App\Repositories\Contracts\CurrencyRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class CurrencyRepository implements CurrencyRepositoryInterface
{
    protected Currency $currency;

    public function __construct(Currency $currency)
    {
        $this->currency = $currency;
    }

    public function search(array $filters, int $perPage = NumericConstants::DEFAULT_PER_PAGE): LengthAwarePaginator
    {
        $query = $this->currency->newQuery()
            ->withCount(['bookings', 'transactions', 'trips']);

        if (! empty($filters['search'])) {
            $escaped = str_replace(
                ['\\', '%', '_'],
                ['\\\\', '\\%', '\\_'],
                $filters['search']
            );

            $query->where(function (Builder $q) use ($escaped) {
                $q->where('name', 'like', "%{$escaped}%")
                    ->orWhere('code', 'like', "%{$escaped}%")
                    ->orWhere('symbol', 'like', "%{$escaped}%");
            });
        }

        if (array_key_exists('is_active', $filters)) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        return $query->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findById(int $id): ?Currency
    {
        return $this->currency->newQuery()
            ->withCount(['bookings', 'transactions', 'trips'])
            ->find($id);
    }

    public function create(array $data): Currency
    {
        return $this->currency->create($data);
    }

    public function update(int $id, array $data): bool
    {
        $currency = $this->findById($id);

        return $currency ? $currency->update($data) : false;
    }

    public function delete(int $id): bool
    {
        $currency = $this->findById($id);

        return $currency ? $currency->delete() : false;
    }

    public function linkedRecordsCount(int $id): int
    {
        $currency = $this->findById($id);

        if (! $currency) {
            return 0;
        }

        return (int) $currency->bookings_count
            + (int) $currency->transactions_count
            + (int) $currency->trips_count;
    }
}