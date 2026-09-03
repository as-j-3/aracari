<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProviderServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'account_id' => $this->id,
            'account_uuid' => $this->uuid,
            'logo' => $this->brand_photo ? url('storage/' . ltrim($this->brand_photo, '/')) : null,
            'provider_name' => $this->name,
            'service_type' => $this->typeOfService?->type_name,
            'country_of_origin' => $this->born_country,
            'registered_at' => $this->created_at?->format('Y-m-d'),
            'status' => $this->status,
        ];
    }
}