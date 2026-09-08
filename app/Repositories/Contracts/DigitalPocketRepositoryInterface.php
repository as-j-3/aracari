<?php

namespace App\Repositories\Contracts;

use App\Constants\NumericConstants;
use App\Models\DigitalPocket;
use Illuminate\Pagination\LengthAwarePaginator;


interface DigitalPocketRepositoryInterface
{

    public function search(array $filters, int $perPage = NumericConstants::DEFAULT_PER_PAGE): LengthAwarePaginator;


    public function findById(int $id): ?DigitalPocket;


    public function create(array $data): DigitalPocket;


    public function update(int $id, array $data): bool;


    public function delete(int $id): bool;


    public function hasReceiveRevenueMethods(int $id): bool;
}