<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CityResource extends JsonResource
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
        'name' => $this->display_name ?? $this->name,
        'country' => $this->whenLoaded('country') ? $this->country->name : null,
        'country_uuid' => $this->whenLoaded('country') 
            ? $this->country->uuid 
            : null,
        'image' => $this->image_url,
        'station' => is_array($this->pickup_points) ? ($this->pickup_points[0] ?? null) : null,
    ];
}
}
