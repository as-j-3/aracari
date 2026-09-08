<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProviderSettlementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'name' => $this->name,

            'email' => $this->email,

            'provider_service' => $this->provider_service,

            'balances' => $this->balances,

            'total_base' => $this->total_base,

            'revenue_methods' => $this->revenue_methods,
        ];
    }
}
