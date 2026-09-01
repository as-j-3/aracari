<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use App\Constants\NumericConstants;
use Illuminate\Pagination\LengthAwarePaginator;


interface UserRepositoryInterface
{

    public function all(int $perPage = NumericConstants::DEFAULT_PER_PAGE): LengthAwarePaginator;


    public function getUsers(array $filters = [], int $perPage = NumericConstants::DEFAULT_PER_PAGE): LengthAwarePaginator;


    public function findById(int $id): ?User;


    public function findByUuid(string $uuid): ?User;


    public function findByPhoneNumber(string $phoneNumber): ?User;


    public function findByEmail(string $email): ?User;


    public function create(array $data): User;


    public function update(int $id, array $data): bool;


    public function delete(int $id): bool;


    public function getByType(string $typeCode, int $perPage = NumericConstants::DEFAULT_PER_PAGE): LengthAwarePaginator;


    public function getByStatus(string $statusCode, int $perPage = NumericConstants::DEFAULT_PER_PAGE): LengthAwarePaginator;


    public function search(string $search, int $perPage = NumericConstants::DEFAULT_PER_PAGE): LengthAwarePaginator;


    public function getApproved(int $perPage = NumericConstants::DEFAULT_PER_PAGE): LengthAwarePaginator;


    public function getPending(int $perPage = NumericConstants::DEFAULT_PER_PAGE): LengthAwarePaginator;


    public function approve(int $id): bool;

    public function getStatistics(): array;
}
