<?php

namespace App\Traits;

use App\Constants\UserTypes;
use App\Models\ProviderBranch;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

trait BranchAccessTrait
{

    public function hasAccessToBranch(int $branchId): bool
    {
        if ($this->isProviderOwner()) {
            return true;
        }

        return DB::table('branch_users')
            ->where('user_id', $this->id)
            ->where('branch_id', $branchId)
            ->exists();
    }


    public function getAccessibleBranches(): Collection
    {
        if ($this->isProviderOwner()) {
            $providerService = $this->providerService;
            if ($providerService) {
                return ProviderBranch::where('provider_id', $providerService->id)->get();
            }
            return collect();
        }

        return $this->branches;
    }


    public function isProviderOwner(): bool
    {
        $cacheKey = "user_{$this->id}_is_provider_owner";
        
        return Cache::remember($cacheKey, 3600, function () {
            return $this->type_user_code === UserTypes::PROVIDER && $this->user_parent === null;
        });
    }


    public function isProviderStaff(): bool
    {
        $cacheKey = "user_{$this->id}_is_provider_staff";
        
        return Cache::remember($cacheKey, 3600, function () {
            return $this->type_user_code === UserTypes::PROVIDER && $this->user_parent !== null;
        });
    }


    public function hasBranchPermission(int $branchId, string $permission): bool
    {
        if ($this->isProviderOwner()) {
            return true;
        }

        if (!$this->hasPermission($permission)) {
            return false;
        }

        $branchSpecificPermissions = [
            'branches:edit-specific',
            'branches:view-specific',
            'branches:staff-manage',
            'branches:reports-view',
            'branches:bookings-manage',
            'branches:trips-manage',
        ];

        if (in_array($permission, $branchSpecificPermissions)) {
            return $this->hasAccessToBranch($branchId);
        }

        return true;
    }


    public function branches()
    {
        return $this->belongsToMany(ProviderBranch::class, 'branch_users');
    }

 
    public function clearBranchAccessCache(): void
    {
        Cache::forget("user_{$this->id}_is_provider_owner");
        Cache::forget("user_{$this->id}_is_provider_staff");
    }
}