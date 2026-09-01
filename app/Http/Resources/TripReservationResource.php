<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TripReservationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $this->load(['trip.waypoints.city', 'trip.waypoints.station', 'tickets.seat', 'tickets.passenger']);

        return [
            'uuid' => $this->uuid,
            'trip' => $this->whenLoaded('trip') ? new TripResource($this->trip) : null,
            'direction' => $this->direction,
            'status' => $this->status,
            'cancelled_at' => $this->cancelled_at?->format('Y-m-d H:i:s'),
            'tickets' => $this->whenLoaded('tickets', function () {
                return $this->tickets->map(function ($ticket) {
                    return [
                        'uuid' => $ticket->uuid,
                        'id' => $ticket->id,
                        'ticket_number' => $ticket->ticket_number,
                        'price_at_booking' => $ticket->price_at_booking,
                        'status' => $ticket->status,
                        'seat' => $ticket->seat ? [
                            'id' => $ticket->seat->id,
                            'name' => $ticket->seat->seat_name,
                        ] : null,
                        'passenger' => $ticket->passenger ? [
                            'id' => $ticket->passenger->id,
                            'name' => $ticket->passenger->full_name,
                            'birthday' => $ticket->passenger->birthday?->format('Y-m-d'),
                        ] : null,
                    ];
                });
            }),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}