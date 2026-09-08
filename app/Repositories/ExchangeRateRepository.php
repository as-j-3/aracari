<?php

namespace App\Repositories;

use App\Constants\NumericConstants;
use App\Models\ExchangeRate;
use App\Repositories\Contracts\ExchangeRateRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class ExchangeRateRepository implements ExchangeRateRepositoryInterface
{
    protected ExchangeRate $exchangeRate;

    public function __construct(ExchangeRate $exchangeRate)
    {
        $this->exchangeRate = $exchangeRate;
    }

    public function search(array $filters, int $perPage = NumericConstants::DEFAULT_PER_PAGE): LengthAwarePaginator
    {
        $query = $this->exchangeRate->newQuery()
            ->with(['fromCurrency', 'toCurrency']);

        if (! empty($filters['from_currency_id'])) {
            $query->where('from_currency_id', $filters['from_currency_id']);
        }

        if (! empty($filters['to_currency_id'])) {
            $query->where('to_currency_id', $filters['to_currency_id']);
        }

        if (! empty($filters['date_from'])) {
            $query->where('rate_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->where('rate_date', '<=', $filters['date_to']);
        }

        if (! empty($filters['search'])) {
            $escaped = str_replace(
                ['\\', '%', '_'],
                ['\\\\', '\\%', '\\_'],
                $filters['search']
            );

            $query->where(function (Builder $q) use ($escaped) {
                $q->whereHas('fromCurrency', fn ($sq) => $sq->where('name', 'like', "%{$escaped}%"))
                    ->orWhereHas('toCurrency', fn ($sq) => $sq->where('name', 'like', "%{$escaped}%"))
                    ->orWhereHas('fromCurrency', fn ($sq) => $sq->where('code', 'like', "%{$escaped}%"))
                    ->orWhereHas('toCurrency', fn ($sq) => $sq->where('code', 'like', "%{$escaped}%"));
            });
        }

        return $query->latest('rate_date')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findById(int $id): ?ExchangeRate
    {
        return $this->exchangeRate->newQuery()
            ->with(['fromCurrency', 'toCurrency'])
            ->find($id);
    }

    public function create(array $data): ExchangeRate
    {
        return $this->exchangeRate->create($data);
    }

    public function update(int $id, array $data): bool
    {
        $rate = $this->findById($id);

        return $rate ? $rate->update($data) : false;
    }

    public function delete(int $id): bool
    {
        $rate = $this->findById($id);

        return $rate ? $rate->delete() : false;
    }
}