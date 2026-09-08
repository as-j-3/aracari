<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CurrencyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'name' => $this->name,

            'symbol' => $this->symbol,

            'code' => $this->code,

            'rate_source' => $this->rate_source,

            'last_rate_update' => $this->last_rate_update?->toISOString(),

            'bookings_count' => (int) ($this->bookings_count ?? 0),

            'transactions_count' => (int) ($this->transactions_count ?? 0),

            'trips_count' => (int) ($this->trips_count ?? 0),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}