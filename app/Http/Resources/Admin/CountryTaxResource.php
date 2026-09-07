<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CountryTaxResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,

            'tax' => [
                'id' => $this->tax?->id,
                'uuid' => $this->tax?->uuid,
                'name' => $this->tax?->name,
            ],

            'country' => [
                'id' => $this->country?->id,
                'name' => $this->country?->name,
                'iso_code' => $this->country?->iso_code,
            ],

            'type' => $this->type,

            'rate' => $this->type === 'percentage'
                ? (float) $this->rate
                : null,

            'amount' => $this->type === 'fixed'
                ? (float) $this->amount
                : null,

            'is_active' => (bool) $this->is_active,

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}