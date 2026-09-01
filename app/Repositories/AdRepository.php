<?php

namespace App\Repositories;

use App\Models\Ad;
use App\Repositories\Contracts\AdRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AdRepository implements AdRepositoryInterface
{
    public function getAllAds(int $perPage, ?string $search, ?string $status): LengthAwarePaginator
    {
        $query = Ad::with('user:id,name,email');

        if ($search !== null && $search !== '') {
            $safeSearch = str_replace(['%', '_'], ['\\%', '\\_'], $search);
            $query->where(function ($q) use ($safeSearch) {
                $q->where('title', 'like', "%{$safeSearch}%")
                  ->orWhere('description', 'like', "%{$safeSearch}%")
                  ->orWhere('uuid', 'like', "%{$safeSearch}%");
            });
        }

        if ($status !== null && $status !== '') {
            $allowedStatuses = ['active', 'pending', 'inactive', 'rejected'];
            if (array_key_exists($status, array_flip($allowedStatuses))) {
                $query->where('status', $status);
            }
        }

        return $query->latest()->paginate($perPage);
    }

    public function findByUuid(string $uuid, array $relations = []): ?Ad
    {
        return Ad::where('uuid', $uuid)
            ->with($relations)
            ->first();
    }

    public function create(array $data): Ad
    {
        return Ad::create($data);
    }

    public function update(Ad $ad, array $data): bool
    {
        return $ad->update($data);
    }

    public function delete(Ad $ad): bool
    {
        return $ad->delete();
    }

    public function getStatistics(): array
    {
        $total = Ad::count();
        $active = Ad::where('status', 'active')->count();
        $pending = Ad::where('status', 'pending')->count();
        $inactive = Ad::whereIn('status', ['inactive', 'rejected'])->count();

        return [
            'total' => $total,
            'active' => $active,
            'pending' => $pending,
            'inactive' => $inactive,
        ];
    }
}
