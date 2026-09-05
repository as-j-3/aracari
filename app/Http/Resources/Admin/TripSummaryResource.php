<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TripSummaryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $seatTotal = $this->total_seats ?? ($this->ticket_limit ?? ($this->vehicle?->seats_count ?? 0));
        $seatAvailable = $this->available_seats ?? 0;
        $seatBooked = max(0, $seatTotal - $seatAvailable);

        $providerName = $this->vehicle?->user?->providerService?->name 
            ?? $this->user?->name 
            ?? '—';

        return [
            'uuid'                => $this->uuid,
            'vehicle'             => [
                'name'         => $this->vehicle?->name ?: ($this->vehicle?->model ?: '—'),
                'model'        => $this->vehicle?->model,
            ],
            'provider'            => [
                'name' => $providerName,
                'logo' => $this->vehicle?->user?->providerService?->brand_photo_url,
            ],
            'route'               => [
                'departure_city'   => $this->departureCity?->name ?? '—',
                'destination_city' => $this->destinationCity?->name ?? '—',
            ],
            'departure_time'      => $this->departure_time?->format('Y-m-d H:i:s'),
            'arrival_time'        => $this->arrival_time?->format('Y-m-d H:i:s'),
            'seats'               => [
                'total'     => (int) $seatTotal,
                'available' => (int) $seatAvailable,
                'booked'    => (int) $seatBooked,
            ],
            'trip_status'         => $this->tripStatus?->status_name ?? (string) $this->trip_status_id,
            'is_public'           => (bool) $this->is_public,
            'created_at'          => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
