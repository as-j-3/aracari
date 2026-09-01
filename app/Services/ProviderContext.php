<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ProviderContext
{
    public function providerServiceIdFor(?User $user): ?int
    {
        return $this->resolveContext($user)['provider_service_id'];
    }

    public function providerUserIdFor(?User $user): ?int
    {
        return $this->resolveContext($user)['provider_user_id'];
    }

    public function clearFor(?User $user): void
    {
        if (!$user?->id) {
            return;
        }

        Cache::forget($this->cacheKey($user->id));
    }

    private function resolveContext(?User $user): array
    {
        if (!$user?->id) {
            return [
                'provider_service_id' => null,
                'provider_user_id' => null,
            ];
        }

        return Cache::remember(
            $this->cacheKey($user->id),
            now()->addMinutes(10),
            fn (): array => $this->lookupContext($user->id, $user->user_parent)
        );
    }

    private function lookupContext(int $userId, ?int $parentUserId): array
    {
        $providerService = DB::table('provider_services')
            ->select('id', 'user_id')
            ->where('user_id', $userId)
            ->first();

        if ($providerService) {
            return [
                'provider_service_id' => (int) $providerService->id,
                'provider_user_id' => (int) $providerService->user_id,
            ];
        }

        if ($parentUserId) {
            $parentProviderService = DB::table('provider_services')
                ->select('id', 'user_id')
                ->where('user_id', $parentUserId)
                ->first();

            if ($parentProviderService) {
                return [
                    'provider_service_id' => (int) $parentProviderService->id,
                    'provider_user_id' => (int) $parentProviderService->user_id,
                ];
            }
        }

        $branchProvider = DB::table('branch_users')
            ->join('provider_branches', 'provider_branches.id', '=', 'branch_users.branch_id')
            ->join('provider_services', 'provider_services.id', '=', 'provider_branches.provider_id')
            ->where('branch_users.user_id', $userId)
            ->select('provider_services.id as provider_service_id', 'provider_services.user_id as provider_user_id')
            ->first();

        return [
            'provider_service_id' => $branchProvider ? (int) $branchProvider->provider_service_id : null,
            'provider_user_id' => $branchProvider ? (int) $branchProvider->provider_user_id : null,
        ];
    }

    private function cacheKey(int $userId): string
    {
        return 'provider_context_user_' . $userId;
    }
}
