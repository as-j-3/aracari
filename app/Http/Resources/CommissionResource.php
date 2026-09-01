<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\ProviderResource;
use App\Http\Resources\ServiceResource;

class CommissionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $this->load(['serviceCommissions.provider', 'serviceCommissions.service', 'bookingCommissions.provider']);
        
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'commission_type' => $this->commission_type,
            'calculation_level' => $this->calculation_level,
            'description' => $this->description,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'service_commissions' => $this->whenLoaded('serviceCommissions', function () {
                return $this->serviceCommissions->map(function ($sc) {
                    return [
                        'id' => $sc->id,
                        'provider_id' => $sc->provider_id,
                        'service_id' => $sc->service_id,
                        'value' => $sc->value,
                        'provider' => $sc->whenLoaded('provider') ? new ProviderResource($sc->provider) : null,
                        'service' => $sc->whenLoaded('service') ? new ServiceResource($sc->service) : null,
                        'calculation_type' => $sc->commission?->commission_type,
                        'min_amount' => $sc->min_amount,
                        'max_amount' => $sc->max_amount,
                    ];
                });
            }),
            'booking_commissions' => $this->whenLoaded('bookingCommissions', function () {
                return $this->bookingCommissions->map(function ($bc) {
                    return [
                        'id' => $bc->id,
                        'provider_id' => $bc->provider_id,
                        'value' => $bc->value,
                        'provider' => $bc->whenLoaded('provider') ? new ProviderResource($bc->provider) : null,
                        'calculation_type' => $bc->commission?->commission_type,
                        'min_amount' => $bc->min_amount,
                        'max_amount' => $bc->max_amount,
                    ];
                });
            }),
        ];
    }
}
