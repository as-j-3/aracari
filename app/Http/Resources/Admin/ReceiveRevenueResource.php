<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReceiveRevenueResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'digital_pocket' => [
                'id' => $this->digitalPocket?->id,
                'pocket_name' => $this->digitalPocket?->pocket_name,
            ],

            'provider' => [
                'id' => $this->provider?->id,
                'name' => $this->provider?->name,
            ],

            'account_number' => $this->account_number_last_4,

            'account_name' => $this->account_name,

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
