<?php

namespace App\Traits;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

trait HasPermissions
{

    public function hasPermission(string $permissionName): bool
    {
        return $this->cachedPermissionNames()->contains($permissionName);
    }


    public function clearPermissionCache(): void
    {
        Cache::forget("user_{$this->id}_permissions");
        Cache::forget("user_{$this->id}_permission_ids");
    }

 
    public function getAllPermissionsAttribute(): Collection
    {
        return $this->cachedPermissionNames();
    }


    public function getAllPermissions(): Collection
    {
        return Cache::remember("user_{$this->id}_permission_ids", 3600, function (): Collection {
            return $this->roles()
                ->with('permissions:id')
                ->get()
                ->flatMap->permissions
                ->pluck('id')
                ->unique()
                ->values();
        });
    }

    private function cachedPermissionNames(): Collection
    {
        return Cache::remember("user_{$this->id}_permissions", 3600, function (): Collection {
            return $this->roles()
                ->with('permissions:id,name')
                ->get()
                ->flatMap->permissions
                ->pluck('name')
                ->unique()
                ->values();
        });
    }
}