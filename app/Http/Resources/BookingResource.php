<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\TripResource;
use App\Http\Resources\TripReservationResource;

class BookingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $this->load([
            'tripReservations.trip',
            'tripReservations.tickets.seat',
            'tripReservations.tickets.passenger',
            'commission',
            'payment.paymentMethod',
            'taxes.tax',
            'currency',
        ]);
        
        return [
            'booking' => [
                'id' => $this->id,
                'uuid' => $this->uuid,
                'amount' => $this->amount ? number_format((float) $this->amount, 2, '.', '') : null,
                'currency' => $this->currency?->code,
                'exchange_rate' => $this->exchange_rate,
                'base_amount' => $this->base_amount 
                    ? number_format((float) $this->base_amount, 2, '.', '') 
                    : null,
                'commission_amount' => $this->commission_amount ? number_format((float) $this->commission_amount, 2, '.', '') : null,
                'commission_value' => $this->commission_value,
                'cancelled_at' => $this->cancelled_at?->format('Y-m-d H:i:s'),
                'note' => $this->note,
                'status' => $this->status ? $this->status->status_name : null,
                'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
                'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            ],
            'user' => new BookingUserResource($this->whenLoaded('user')),
            'branch' => $this->when($this->branch, function () {
                return [
                    'id' => $this->branch->id,
                    'address' => $this->branch->address,
                    'phone_number' => $this->branch->phone_number,
                    'status' => $this->branch->status,
                ];
            }),
            'commission' => $this->when($this->commission, function () {
                return [
                    'id' => $this->commission->id,
                    'commission_type' => $this->commission->commission_type,
                    'calculation_level' => $this->commission->calculation_level,
                    'description' => $this->commission->description,
                ];
            }),
            'trips' => TripReservationResource::collection($this->whenLoaded('tripReservations')),
            'payment' => $this->payment ? [
                'id' => $this->payment->id,
                'amount' => number_format((float) $this->payment->payed_amount, 2, '.', ''),
                'payment_method' => $this->payment->paymentMethod?->method_name,
                'created_at' => $this->payment->created_at?->format('Y-m-d H:i:s'),
            ] : null,
            'taxes' => $this->whenLoaded('taxes', function () {
                return $this->taxes->map(function ($tax) {
                    return [
                        'id' => $tax->id,
                        'tax_name' => $tax->tax_name ?? $tax->tax?->name,
                        'tax_type' => $tax->tax_type_rate_or_amount,
                        'tax_rate' => $tax->tax_rate,
                        'tax_amount' => number_format((float) $tax->tax_amount, 2, '.', ''),
                        'currency' => $tax->currency?->code,
                        'exchange_rate' => $tax->exchange_rate,
                        'base_tax_amount' => $tax->base_tax_amount 
                            ? number_format((float) $tax->base_tax_amount, 2, '.', '') 
                            : null,
                    ];
                });
            }),
            'taxes_total' => $this->whenLoaded('taxes', function () {
                $total = $this->taxes->sum('tax_amount');
                return number_format((float) $total, 2, '.', '');
            }),            
        ];
    }
}