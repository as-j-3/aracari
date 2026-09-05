<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\TripResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingDetailsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'booking' => [
                'uuid' => $this->uuid,
                'amount' => $this->amount,

                'status' => $this->status ? [
                    'id' => $this->status->id,
                    'name' => $this->status->status_name,
                ] : null,

                'created_at' => $this->created_at?->format('Y-m-d H:i'),
                'cancelled_at' => $this->cancelled_at?->format('Y-m-d H:i'),
            ],

            'customer' => $this->user ? [
                'name' => $this->user->name,
                'phone_number' => $this->user->phone_number,
                'email' => $this->user->email,
            ] : null,

            'payment' => $this->payment ? [
                'id' => $this->payment->id,
                'amount' => $this->payment->payed_amount,

                'payment_method' => $this->payment->paymentMethod?->method_name,

                'currency' => $this->payment->currency ? [
                    'code' => $this->payment->currency->code,
                    'name' => $this->payment->currency->name,
                ] : null,

                'exchange_rate' => $this->payment->exchange_rate,
            ] : null,

            'currency' => $this->currency ? [
                'code' => $this->currency->code,
                'name' => $this->currency->name,
            ] : null,

            'trips' => $this->tripReservations?->map(function ($reservation) {
                return [
                    'uuid' => $reservation->uuid,

                    'trip' => $reservation->trip
                        ? new TripResource($reservation->trip)
                        : null,

                    'tickets' => $reservation->tickets?->map(function ($ticket) {
                        return [
                            'id' => $ticket->id,
                            'ticket_number' => $ticket->ticket_number,

                            'passenger' => $ticket->passenger ? [
                                'id' => $ticket->passenger->id,
                                'name' => $ticket->passenger->full_name,
                                'birthday' => $ticket->passenger->birthday,
                                'phone_number' => $ticket->passenger->phone_number,
                            ] : null,

                            'seat' => $ticket->seat ? [
                                'id' => $ticket->seat->id,
                                'name' => $ticket->seat->seat_name,
                            ] : null,

                            'price_at_booking' => $ticket->price_at_booking,
                            'status' => $ticket->status,
                        ];
                    })->values(),
                ];
            })->values(),

            'taxes' => $this->taxes?->map(function ($tax) {
                return [
                    'id' => $tax->id,
                    'name' => $tax->name,
                    'type' => $tax->type,
                    'rate' => $tax->rate,
                    'amount' => $tax->amount,

                    'currency' => $tax->currency ? [
                        'code' => $tax->currency->code,
                        'name' => $tax->currency->name,
                    ] : null,
                ];
            })->values(),

            'coupons' => $this->couponUsages?->map(function ($usage) {
                return [
                    'id' => $usage->coupon?->id,
                    'code' => $usage->coupon?->code,
                    'discount_type' => $usage->coupon?->discount_type,
                    'discount_value' => $usage->coupon?->discount_value,
                ];
            })->values(),
        ];
    }
}