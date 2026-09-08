<?php

namespace App\Repositories\Contracts;

use App\Constants\NumericConstants;
use App\Models\PaymentMethod;
use Illuminate\Pagination\LengthAwarePaginator;


interface PaymentMethodRepositoryInterface
{

    public function search(array $filters, int $perPage = NumericConstants::DEFAULT_PER_PAGE): LengthAwarePaginator;


    public function findById(int $id): ?PaymentMethod;


    public function create(array $data): PaymentMethod;


    public function update(int $id, array $data): bool;


    public function delete(int $id): bool;


    public function isSystemMethod(int $id): bool;


    public function hasBookingPayments(int $id): bool;
}