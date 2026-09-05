<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehicleTypeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'uuid'           => $this->uuid,
            'type_name'      => $this->type_name,
            'vehicles_count' => (int) ($this->vehicles_count ?? 0),
            'created_at'     => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
