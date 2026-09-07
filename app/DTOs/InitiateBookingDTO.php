<?php

namespace App\DTOs;

class InitiateBookingDTO
{
    public function __construct(
        public string $tripUuid,
        public int $userId,
        public int $adults,
        public int $children,
        public array $passengersData,
        public ?string $departureDate = null,
        public ?string $couponCode = null,
        public ?int $currencyId = null,
        public int $holdDurationMinutes = 2
    ) {}

    public static function fromRequest(array $data, int $userId, string $tripUuid): self
    {
        return new self(
            tripUuid: $tripUuid,
            userId: $userId,
            adults: (int) ($data['adults'] ?? 1),
            children: (int) ($data['children'] ?? 0),
            passengersData: $data['passengers'] ?? [],
            departureDate: $data['departure_date'] ?? null,
            couponCode: $data['coupon_code'] ?? null,
            currencyId: isset($data['currency_id']) ? (int) $data['currency_id'] : null,
            holdDurationMinutes: 10
        );
    }
}
