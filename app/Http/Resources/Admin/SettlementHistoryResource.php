<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SettlementHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'uuid' => $this->uuid,

            'reference_number' => $this->reference_number,

            'reference_type' => $this->reference_type,

            'reference_id' => $this->reference_id,

            'status' => $this->status,

            'description' => $this->description,

            'creator' => [
                'id' => $this->creator?->id,
                'name' => $this->creator?->name,
            ],

            'total_amount' => $this->getTotalAmount(),

            'is_balanced' => $this->isBalanced(),

            'transactions' => $this->transactions->map(fn ($t) => [
                'id' => $t->id,
                'transaction_type' => $t->transaction_type,
                'amount' => $t->amount,
                'currency_id' => $t->currency_id,
                'exchange_rate' => $t->exchange_rate,
                'base_amount' => $t->base_amount,
                'description' => $t->description,
            ]),

            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
