<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;


class ProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid'             => $this->uuid,
            'name'             => $this->name,
            'phone_number'     => $this->phone_number,
            'email'            => $this->email,
            'is_approved'      => (bool) $this->is_approved,
            'status'           => $this->status?->status_code,
            'user_type'        => $this->userType?->type_name,
            'provider_service' => new ProviderServiceResource($this->whenLoaded('providerService')),
            'created_at'       => $this->created_at?->toIso8601String(),
        ];
    }
}