<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CouponResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'uuid' => $this->uuid,

            'code' => $this->code,

            'percentage' => $this->percentage,

            'status' => [
                'id' => $this->status?->id,
                'name' => $this->status?->name,
            ],

            'start_date' => $this->start_date?->toISOString(),

            'end_date' => $this->end_date?->toISOString(),

            'usage_limit' => $this->usage_limit,

            'max_usage_per_user' => $this->max_usage_per_user,

            'minimum_amount' => $this->minimum_amount,

            'description' => $this->description,

            'used_count' => $this->used_count,

            'usage_percentage' => $this->usage_percentage,

            'is_expired' => $this->is_expired,

            'is_valid' => $this->is_valid,

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
