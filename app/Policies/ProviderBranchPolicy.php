<?php

namespace App\Policies;

use App\Models\ProviderBranch;
use App\Models\User;
use App\Services\ProviderContext;

class ProviderBranchPolicy
{
    public function __construct(
        private ProviderContext $providerContext
    ) {}


    public function access(User $user, ProviderBranch $branch): bool
    {
        $providerServiceId = $this->providerContext->providerServiceIdFor($user);

        if (!$providerServiceId || $branch->provider_id !== $providerServiceId) {
            return false;
        }

        if ($user->isProviderOwner()) {
            return true;
        }

        return $user->hasAccessToBranch($branch->id);
    }

    public function create(User $user): bool
    {
        return $user->isProviderOwner();
    }


    public function update(User $user, ProviderBranch $branch): bool
    {
        return $this->access($user, $branch);
    }


    public function delete(User $user, ProviderBranch $branch): bool
    {
        return $this->access($user, $branch);
    }
}