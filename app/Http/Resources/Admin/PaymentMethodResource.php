<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentMethodResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'method_name' => $this->method_name,

            'provider' => [
                'id' => $this->provider?->id,
                'name' => $this->provider?->name,
            ],

            'country' => [
                'id' => $this->country?->id,
                'name' => $this->country?->name,
            ],

            'currency' => [
                'id' => $this->currency?->id,
                'code' => $this->currency?->code,
                'name' => $this->currency?->name,
            ],

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}