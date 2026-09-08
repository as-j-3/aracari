<?php

namespace App\Repositories\Contracts;

use App\Constants\NumericConstants;
use App\Models\Currency;
use Illuminate\Pagination\LengthAwarePaginator;


interface CurrencyRepositoryInterface
{

    public function search(array $filters, int $perPage = NumericConstants::DEFAULT_PER_PAGE): LengthAwarePaginator;


    public function findById(int $id): ?Currency;


    public function create(array $data): Currency;


    public function update(int $id, array $data): bool;


    public function delete(int $id): bool;


    public function linkedRecordsCount(int $id): int;
}