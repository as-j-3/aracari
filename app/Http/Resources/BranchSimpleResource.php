<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BranchSimpleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'address' => $this->address,
            'phone_number' => $this->phone_number,
            'status' => $this->status,
            'city' => $this->whenLoaded('city') ? [
                'uuid' => $this->city->uuid,
                'name' => $this->city->name,
            ] : null,
        ];
    }
}