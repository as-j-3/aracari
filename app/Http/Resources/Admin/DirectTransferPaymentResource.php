<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DirectTransferPaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'uuid' => $this->uuid,

            'name' => $this->name,

            'phone_number' => $this->phone_number,

            'bank_name' => $this->bank_name,

            'bank_account_number' => $this->bank_account_number,

            'payment_method' => [
                'id' => $this->paymentMethod?->id,
                'method_name' => $this->paymentMethod?->method_name,
            ],

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}