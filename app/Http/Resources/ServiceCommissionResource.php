<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\ProviderResource;
use App\Http\Resources\ServiceResource;
use App\Http\Resources\CommissionResource;

class ServiceCommissionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $this->load(['provider', 'service', 'commission']);
        
        return [
            'id' => $this->id,
            'provider_id' => $this->provider_id,
            'service_id' => $this->service_id,
            'commission_id' => $this->commission_id,
            'value' => $this->value,
            'calculation_type' => $this->commission?->commission_type,
            'min_amount' => $this->min_amount,
            'max_amount' => $this->max_amount,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'provider' => $this->whenLoaded('provider') ? new ProviderResource($this->provider) : null,
            'service' => $this->whenLoaded('service') ? new ServiceResource($this->service) : null,
            'commission' => $this->whenLoaded('commission') ? new CommissionResource($this->commission) : null,
        ];
    }
}
