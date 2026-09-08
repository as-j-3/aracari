<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExchangeRateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'from_currency' => [
                'id' => $this->fromCurrency?->id,
                'name' => $this->fromCurrency?->name,
                'code' => $this->fromCurrency?->code,
                'symbol' => $this->fromCurrency?->symbol,
            ],

            'to_currency' => [
                'id' => $this->toCurrency?->id,
                'name' => $this->toCurrency?->name,
                'code' => $this->toCurrency?->code,
                'symbol' => $this->toCurrency?->symbol,
            ],

            'rate' => (float) $this->rate,

            'rate_date' => $this->rate_date?->toDateString(),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}