<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DigitalPocketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'pocket_name' => $this->pocket_name,

            'currency' => [
                'id' => $this->currency?->id,
                'name' => $this->currency?->name,
                'code' => $this->currency?->code,
                'symbol' => $this->currency?->symbol,
            ],

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}