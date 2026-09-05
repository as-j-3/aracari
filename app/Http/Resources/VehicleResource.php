<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehicleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'model' => $this->model,
            'outside_photo_url' => $this->outside_photo_url,
            'inside_photo_url' => $this->inside_photo_url,
            'seats_count' => $this->seats_count,
            'status' => $this->status,
            'vehicle_type' => $this->vehicleType?->type_name,
            'provider' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user?->id,
                    'uuid' => $this->user?->uuid,
                    'name' => $this->user?->name,
                    'email' => $this->user?->email,
                ];
            }),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
