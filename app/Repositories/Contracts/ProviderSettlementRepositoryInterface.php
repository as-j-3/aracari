<?php

namespace App\Repositories\Contracts;

use App\Models\TransactionReference;
use Illuminate\Pagination\LengthAwarePaginator;

interface ProviderSettlementRepositoryInterface
{
    public function getProvidersWithBalances(): array;

    public function settleProvider(
        int $providerUserId,
        float $amount,
        ?int $revenueMethodId = null,
        ?int $actorId = null,
        ?int $currencyId = null
    ): array;

    public function history(array $filters, int $perPage = 20): LengthAwarePaginator;
}
