<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\ProviderResource;
use App\Http\Resources\CountryResource;
use App\Http\Resources\CityResource;

class ProviderBranchResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        $this->load(['provider', 'country', 'city', 'branchUsers.user.userRole']);
        
        return [
            'id' => $this->id,
            'provider_id' => $this->provider_id,
            'phone_number' => $this->phone_number,
            'country_id' => $this->country_id,
            'city_id' => $this->city_id,
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'status' => $this->status,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'country' => $this->whenLoaded('country') ? new CountryResource($this->country) : null,
            'city' => $this->whenLoaded('city') ? new CityResource($this->city) : null,
            'provider' => $this->whenLoaded('provider') ? new ProviderResource($this->provider) : null,
            'users' => $this->whenLoaded('branchUsers', function () {
                return $this->branchUsers->map(function ($branchUser) {
                    return [
                        'id' => $branchUser->id,
                        'user_id' => $branchUser->user_id,
                        'role' => $branchUser->user->userRole?->name ?? 'غير محدد',
                        'user' => $branchUser->user ? [
                            'id' => $branchUser->user->id,
                            'name' => $branchUser->user->name,
                            'email' => $branchUser->user->email,
                        ] : null,
                    ];
                });
            }),
        ];
    }
}
