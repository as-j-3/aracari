<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserDirectPaymentInfoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,

            'direct_transfer_payment' => [
                'id' => $this->directTransferPayment?->id,
                'name' => $this->directTransferPayment?->name,
                'phone_number' => $this->directTransferPayment?->phone_number,
                'bank_name' => $this->directTransferPayment?->bank_name,
                'bank_account_number' =>
                    $this->directTransferPayment?->bank_account_number,
            ],

            'booking_payment_id' => $this->booking_payment_id,

            'receipt_number' => $this->receipt_number,

            'sender_name' => $this->sender_name,

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}