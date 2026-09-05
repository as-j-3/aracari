<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,

            'customer_name' => $this->user?->name,

            'phone_number' => $this->user?->phone_number,

            'booking_date' => $this->created_at?->format('Y-m-d H:i'),

            'amount' => $this->amount,

            'status' => $this->status,
        ];
    }
}