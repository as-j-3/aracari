<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TripResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $this->loadMissing(['waypoints.city']);
        $repeat = $this->repeats?->first();

        $totalSeats = $this->vehicle?->seats()->count() ?? 0;
        $availableSeats = $this->available_seats ?? ($totalSeats - count($this->getBookedSeatIds()));

        return [
            'uuid' => $this->uuid,
            'image' => $this->image_url,
            'provider' => $this->when($this->user, function () {
                return [
                    'id'=> $this->user->id,
                    'uuid' => $this->user->uuid,
                    'name' => $this->user->name,
                    'logo' => $this->user->providerService?->brand_photo_url,
                ];
            }),
            'available_seats' => $availableSeats,
            'total_seats' => $totalSeats,
            'is_public' => $this->is_public,
            'departure_city' => $this->departureCity?->name,
            'departure_city_uuid' => $this->whenLoaded('departureCity') ? $this->departureCity?->uuid : null,
            'destination_city' => $this->destinationCity?->name,
            'destination_city_uuid' => $this->whenLoaded('destinationCity') ? $this->destinationCity?->uuid : null,
            'pickup_location' => $this->departureStation ? [
                'uuid' => $this->departureStation->uuid,
                'name' => $this->departureStation->name,
            ] : null,

            'arrival_location' => $this->arrivalStation ? [
                'uuid' => $this->arrivalStation->uuid,
                'name' => $this->arrivalStation->name,
            ] : null,
            'waypoints' => $this->whenLoaded('waypoints', function () {
                return $this->waypoints->map(function ($waypoint) {
                    return [
                        'uuid' => $waypoint->uuid,
                        'city' => $waypoint->city ? [
                            'uuid' => $waypoint->city->uuid,
                            'name' => $waypoint->city->name,
                        ] : null,
                        'station' => $waypoint->station ? [
                            'uuid' => $waypoint->station->uuid,
                            'name' => $waypoint->station->name,
                        ] : null,
                    ];
                });
            }),
            'check_in_time' => $this->check_in_time?->format('Y-m-d H:i:s'),
            'departure_time' => $this->departure_time?->format('Y-m-d H:i:s'),
            'arrival_time' => $this->arrival_time?->format('Y-m-d H:i:s'),
            'price_for_adult' => number_format((float) $this->price_for_adult, 2, '.', ''),
            'price_for_child' => number_format((float) $this->price_for_child, 2, '.', ''),
            'discount_percentage' => number_format((float) $this->discount_percentage, 2, '.', ''),

            // 🟢 إرجاع العملة هنا على مستوى الرحلة مباشرةً
            'currency' => $this->whenLoaded('currency', function () {
                return [
                    'id'     => $this->currency->id,
                    'name'   => $this->currency->name,  
                    'symbol' => $this->currency->symbol, 
                    'code'   => $this->currency->code,  
                ];
            }),

            'conditions' => $this->conditions,
            'policies' => $this->policies->map(function ($policy) {
                return [
                    'id' => $policy->id,
                    'uuid' => $policy->uuid,
                    'name' => $policy->name,
                    'description' => $policy->description ?? $policy->name,
                ];
            }),
            'type_of_trip' => $this->typeOfTrip?->type_name,
            'level_trip' => $this->levelTrip?->level_name,
            'trip_status' => $this->tripStatus?->status_name,
            'vehicle' => new VehicleResource($this->whenLoaded('vehicle')),
            'amenities' => TripAmenityResource::collection($this->whenLoaded('tripAmenities')),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'parent_trip_uuid' => $this->when($this->parent_id, fn() => $this->parentTrip?->uuid),
            'child_trips' => $this->whenLoaded('childTrips', fn() => $this->childTrips?->map(fn($child) => [
                'uuid' => $child->uuid,
                'departure_time' => $child->departure_time?->format('Y-m-d H:i:s'),
                'arrival_time' => $child->arrival_time?->format('Y-m-d H:i:s'),
            ])->values() ?? []),

            'is_recurring' => !is_null($repeat),
            'recurrence' => !is_null($repeat) ? [
                'start_date' => $repeat->start_date?->format('Y-m-d'),
                'end_date' => $repeat->end_date?->format('Y-m-d'),
                'status' => $repeat->status,
                'days' => $repeat->relationLoaded('repeatedDays')
                    ? $repeat->repeatedDays->pluck('day_of_week')->values()
                    : [],
            ] : null,
        ];
    }
}