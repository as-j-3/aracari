<?php

namespace App\Repositories\Contracts;

use App\Constants\NumericConstants;
use App\Models\ExchangeRate;
use Illuminate\Pagination\LengthAwarePaginator;


interface ExchangeRateRepositoryInterface
{

    public function search(array $filters, int $perPage = NumericConstants::DEFAULT_PER_PAGE): LengthAwarePaginator;


    public function findById(int $id): ?ExchangeRate;


    public function create(array $data): ExchangeRate;


    public function update(int $id, array $data): bool;


    public function delete(int $id): bool;
}