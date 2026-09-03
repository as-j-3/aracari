<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            
            'commission_type' => $this->commission_type,
            'calculation_level' => $this->calculation_level,
            'description' => $this->description,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'booking_commissions' => $this->whenLoaded('bookingCommissions', function () {
                return $this->bookingCommissions->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'value' => $item->value,
                        'provider' => $item->provider ? [
                            'id' => $item->provider->id,
                            'uuid' => $item->provider->uuid,
                            'name' => $item->provider->name,
                        ] : null,
                    ];
                });
            }),
            'currency_id' => $this->currency_id,
            'currency' => $this->whenLoaded('currency', function () {
                return $this->currency ? [
                    'id' => $this->currency->id,
                    'name' => $this->currency->name,
                    'symbol' => $this->currency->symbol,
                    'code' => $this->currency->code,
                ] : null;
            }),
            'service_commissions' => $this->whenLoaded('serviceCommissions', function () {
                return $this->serviceCommissions->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'value' => $item->value,
                        'service_id' => $item->service_id,
                        'provider' => $item->provider ? [
                            'id' => $item->provider->id,
                            'uuid' => $item->provider->uuid,
                            'name' => $item->provider->name,
                        ] : null,
                    ];
                });
            }),
        ];
    }
}
