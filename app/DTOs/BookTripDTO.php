<?php

namespace App\DTOs;

use App\Constants\PaymentMethod as PaymentMethodConst;


class BookTripDTO
{

    public array $tripUuids;

    public int $adults;

 
    public int $children;


    public int $userId;


    public int $paymentMethodId = PaymentMethodConst::CASH;
    public int $paymentCurrencyId;

    public array $departureDates = [];


    public array $passengersData = [];


    public ?string $couponCode = null;

    public ?string $outboundCouponCode = null;
    public ?string $returnCouponCode = null;

    public ?string $stripeToken = null;
    public array $outboundSeatIds = [];
    public array $returnSeatIds = [];

    public function __construct(
        array $tripUuids,
        int $adults,
        int $children,
        int $userId,
        int $paymentMethodId = PaymentMethodConst::CASH,
        array $departureDates = [],
        array $passengersData = [],
        ?string $couponCode = null,
        ?string $outboundCouponCode = null,
        ?string $returnCouponCode = null,
        ?string $stripeToken = null,
        array $outboundSeatIds = [],  
        array $returnSeatIds = [],    
         int $paymentCurrencyId = 0 
    ) {
        $this->tripUuids = $tripUuids;
        $this->adults = $adults;
        $this->children = $children;
        $this->userId = $userId;
        $this->paymentMethodId = $paymentMethodId;
        $this->departureDates = $departureDates;
        $this->passengersData = $passengersData;
        $this->couponCode = $couponCode;
        $this->outboundCouponCode = $outboundCouponCode;
        $this->returnCouponCode = $returnCouponCode;
        $this->stripeToken = $stripeToken;
        $this->outboundSeatIds = $outboundSeatIds;  
        $this->returnSeatIds = $returnSeatIds;
        $this->paymentCurrencyId = $paymentCurrencyId;   
    }


    public static function fromRequest(array $data, int $userId, string|array $tripUuid): self
    {
            $baseCurrencyId = app(\App\Services\CurrencyService::class)->baseCurrencyId() ?? 1;

        return new self(
            tripUuids: is_array($tripUuid) ? $tripUuid : [$tripUuid],
            adults: (int) ($data['adults'] ?? 1),
            children: (int) ($data['children'] ?? 0),
            userId: $userId,
            paymentMethodId: (int) ($data['payment_method_id'] ?? PaymentMethodConst::CASH),
            departureDates: (array) ($data['departure_dates'] ?? []),
            passengersData: $data['passengers'] ?? [],
            couponCode: $data['coupon_code'] ?? null,
            outboundCouponCode: $data['outbound_coupon_code'] ?? null,
            returnCouponCode: $data['return_coupon_code'] ?? null,
            stripeToken: $data['stripe_token'] ?? null,
            outboundSeatIds: array_column($data['passengers'] ?? [], 'outbound_seat_id'), 
            returnSeatIds: array_column($data['passengers'] ?? [], 'return_seat_id'),
            paymentCurrencyId: (int) ($data['currency_id'] ?? 0) ?: $baseCurrencyId

        );
    }
}
