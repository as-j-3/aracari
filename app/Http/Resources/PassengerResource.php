<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PassengerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'full_name' => $this->full_name,
            'phone_number' => $this->phone_number,
            'birthday' => $this->birthday?->format('Y-m-d'),
            'age' => $this->age,
            'gender_id' => $this->gender_id,
            'gender' => $this->whenLoaded('gender', function () {
                return [
                    'id' => $this->gender?->id,
                    'name' => $this->gender?->name,
                ];
            }, $this->gender?->name),
            'personal_card_id' => $this->personal_card_id,
            'personal_country_place_of_issue' => $this->personal_country_place_of_issue,
            'personal_city_place_of_issue' => $this->personal_city_place_of_issue,
            'personal_country' => $this->whenLoaded('personalCountry', fn () => $this->personalCountry?->name),
            'personal_city' => $this->whenLoaded('personalCity', fn () => $this->personalCity?->name),
            'personal_card_date_issu' => $this->personal_card_date_issu?->format('Y-m-d'),
            'personal_card_date_expiry' => $this->personal_card_date_expiry?->format('Y-m-d'),
            'personal_card_id_photo' => $this->personal_card_id_photo,
            'personal_card_id_photo_url' => $this->personal_card_id_photo_url,
            'passport_number' => $this->passport_number,
            'passport_country_place_of_issue' => $this->passport_country_place_of_issue,
            'passport_city_place_of_issue' => $this->passport_city_place_of_issue,
            'passport_country' => $this->whenLoaded('passportCountry', fn () => $this->passportCountry?->name),
            'passport_city' => $this->whenLoaded('passportCity', fn () => $this->passportCity?->name),
            'passport_date_issu' => $this->passport_date_issu?->format('Y-m-d'),
            'passport_date_expiry' => $this->passport_date_expiry?->format('Y-m-d'),
            'passport_photo' => $this->passport_photo,
            'passport_photo_url' => $this->passport_photo_url,
            'created_by' => $this->created_by,
            'creator' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator?->id,
                    'name' => $this->creator?->name,
                    'email' => $this->creator?->email,
                ];
            }),
            'trip_reservations_count' => $this->whenCounted('tripReservations'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}