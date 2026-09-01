<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'phone_number' => $this->phone_number,
            'is_approved' => (bool)$this->is_approved,
            'status' => $this->status?->status_code,
            'user_type' => $this->userType?->type_name,
            'provider_service' => new ProviderServiceResource($this->whenLoaded('providerService')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        
        ];
    }


    private function shouldShowEmail(Request $request): bool
    {
        return $request->user() && (
            $request->user()->isAdmin() || 
            $request->user()->id === $this->id
        );
    }
}
