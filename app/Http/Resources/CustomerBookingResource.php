<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerBookingResource extends JsonResource
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
            'amount' => $this->amount,
            'cancelled_at' => $this->cancelled_at?->format('Y-m-d H:i:s'),
            'note' => $this->note,
            'status' => $this->status ? $this->status->status_name : null,
            'user' => new BookingUserResource($this->whenLoaded('user')),
            'trips' => $this->whenLoaded('tripReservations', function () {
                return $this->tripReservations->map(function ($reservation) {
                    return [
                        'uuid' => $reservation->uuid,
                        'trip' => new TripResource($reservation->trip),
                        'direction' => $reservation->direction,
                        'status' => $reservation->status,
                        'cancelled_at' => $reservation->cancelled_at?->format('Y-m-d H:i:s'),
                        'created_at' => $reservation->created_at?->toIso8601String(),
                        'updated_at' => $reservation->updated_at?->toIso8601String(),
                    ];
                });
            }),
            'payment' => $this->whenLoaded('payment', function () {
                return [
                    'id' => $this->payment->id,
                    'amount' => $this->payment->amount,
                    'payment_method' => $this->payment->payment_method,
                    'created_at' => $this->payment->created_at?->toIso8601String(),
                ];
            }),
            'taxes' => $this->whenLoaded('taxes', function () {
                return $this->taxes->map(function ($tax) {
                    return [
                        'id' => $tax->id,
                        'tax_name' => $tax->tax_name,
                        'tax_amount' => $tax->tax_amount,
                    ];
                });
            }),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
