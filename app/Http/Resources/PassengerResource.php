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
            'full_name' => $this->full_name,
            'birthday' => $this->birthday?->format('Y-m-d'),
            'age' => $this->age,
            'phone_number' => $this->phone_number,
            'gender' => $this->gender?->name,
            'gender_id' => $this->gender_id,
            'personal_card_id' => $this->personal_card_id,
            'personal_card_place_of_issue' => $this->personal_card_place_of_issue,
            'personal_card_date_issu' => $this->personal_card_date_issu?->format('Y-m-d'),
            'personal_card_date_expiry' => $this->personal_card_date_expiry?->format('Y-m-d'),
            'passport_number' => $this->passport_number,
            'passport_date_issu' => $this->passport_date_issu?->format('Y-m-d'),
            'passport_date_expiry' => $this->passport_date_expiry?->format('Y-m-d'),
            'passport_place_of_issue' => $this->passport_place_of_issue,
            'passport_photo' => $this->passport_photo,
        ];
    }
}