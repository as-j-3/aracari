<?php

namespace App\Repositories;

use App\Constants\NumericConstants;
use App\Models\DigitalPocket;
use App\Repositories\Contracts\DigitalPocketRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class DigitalPocketRepository implements DigitalPocketRepositoryInterface
{
    protected DigitalPocket $digitalPocket;

    public function __construct(DigitalPocket $digitalPocket)
    {
        $this->digitalPocket = $digitalPocket;
    }

    public function search(array $filters, int $perPage = NumericConstants::DEFAULT_PER_PAGE): LengthAwarePaginator
    {
        $query = $this->digitalPocket->newQuery()
            ->with('currency');

        if (! empty($filters['search'])) {
            $escaped = str_replace(
                ['\\', '%', '_'],
                ['\\\\', '\\%', '\\_'],
                $filters['search']
            );

            $query->where(fn (Builder $q) => $q->where('pocket_name', 'like', "%{$escaped}%"));
        }

        if (! empty($filters['currency_id'])) {
            $query->where('currency_id', $filters['currency_id']);
        }

        return $query->latest('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findById(int $id): ?DigitalPocket
    {
        return $this->digitalPocket->newQuery()
            ->with('currency')
            ->find($id);
    }

    public function create(array $data): DigitalPocket
    {
        return $this->digitalPocket->create($data);
    }

    public function update(int $id, array $data): bool
    {
        $pocket = $this->findById($id);

        return $pocket ? $pocket->update($data) : false;
    }

    public function delete(int $id): bool
    {
        $pocket = $this->findById($id);

        return $pocket ? $pocket->delete() : false;
    }

    public function hasReceiveRevenueMethods(int $id): bool
    {
        $pocket = $this->findById($id);

        return $pocket ? $pocket->receiveRevenueMethods()->exists() : false;
    }
}