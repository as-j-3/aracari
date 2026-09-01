<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'title' => $this->title,
            'description' => $this->description,
            'image_url' => $this->getImageUrl(),
            'target_url' => $this->target_url,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
        ];
    }

    private function getImageUrl(): ?string
    {
        if (!$this->image_url) {
            return null;
        }
        
        if (filter_var($this->image_url, FILTER_VALIDATE_URL)) {
            return $this->image_url;
        }
        
        return asset('storage/' . $this->image_url);
    }
}