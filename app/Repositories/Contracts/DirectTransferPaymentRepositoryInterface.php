<?php

namespace App\Repositories\Contracts;

use App\Constants\NumericConstants;
use App\Models\DirectTransferPayment;
use Illuminate\Pagination\LengthAwarePaginator;

interface DirectTransferPaymentRepositoryInterface
{
    public function search(array $filters, int $perPage = NumericConstants::DEFAULT_PER_PAGE): LengthAwarePaginator;

    public function findByUuid(string $uuid): ?DirectTransferPayment;

    public function create(array $data): DirectTransferPayment;

    public function update(string $uuid, array $data): bool;

    public function delete(string $uuid): bool;

    public function hasLinkedUserInfo(string $uuid): bool;
}