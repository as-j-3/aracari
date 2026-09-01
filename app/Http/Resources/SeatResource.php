<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SeatResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this['id'] ?? $this->id ?? null,
            'seat_name' => $this['seat_name'] ?? $this['name'] ?? null,
            'is_available' => $this['is_available'] ?? ($this['status'] === 'available'),
            'status' => $this['status'] ?? ($this['is_available'] ? 'available' : 'booked'),
        ];
    }
}