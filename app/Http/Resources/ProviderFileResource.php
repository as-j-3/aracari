<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProviderFileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'file_url' => $this->file_url,
            'file_type' => $this->file_type,
            'status' => $this->status,
            'note' => $this->note,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
