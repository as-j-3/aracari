<?php

namespace App\Repositories;

use App\Models\TransactionReference;
use App\Repositories\Contracts\ProviderSettlementRepositoryInterface;
use App\Services\ProviderSettlementService;
use Illuminate\Pagination\LengthAwarePaginator;

class ProviderSettlementRepository implements ProviderSettlementRepositoryInterface
{
    public function __construct(
        private ProviderSettlementService $settlementService
    ) {}

    public function getProvidersWithBalances(): array
    {
        $providers = $this->settlementService->getProvidersWithBalances();

        return array_map(fn ($item) => (object) $item, $providers);
    }

    public function settleProvider(
        int $providerUserId,
        float $amount,
        ?int $revenueMethodId = null,
        ?int $actorId = null,
        ?int $currencyId = null
    ): array {
        return $this->settlementService->settleProvider(
            providerUserId: $providerUserId,
            amount: $amount,
            revenueMethodId: $revenueMethodId,
            actorId: $actorId,
            currencyId: $currencyId,
        );
    }

    public function history(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = TransactionReference::where('reference_type', 'provider_settlement')
            ->with(['transactions', 'creator:id,name']);

        if (! empty($filters['provider_id'])) {
            $query->where('reference_id', $filters['provider_id']);
        }

        return $query->latest()
            ->paginate($perPage)
            ->withQueryString();
    }
}
