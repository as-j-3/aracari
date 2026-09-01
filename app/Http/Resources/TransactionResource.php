<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'type' => $this->transaction_type,
            'amount' => (float) $this->amount,
            'description' => $this->description,
            'created_at' => $this->created_at->toDateTimeString(),
            'reference' => $this->whenLoaded('reference', function () {
            return [
                'uuid' => $this->reference->uuid,
                'reference_number' => $this->reference->reference_number,
                'created_by' => $this->reference->created_by,
            ];
        }),
        ];
    }
}
