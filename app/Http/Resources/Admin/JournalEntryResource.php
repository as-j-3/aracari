<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JournalEntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'reference_number' => $this->reference_number,
            'reference_type' => $this->reference_type,
            'status' => $this->status,
            'created_by' => $this->created_by,
            'creator' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ]),
            'created_at' => $this->created_at?->toISOString(),
            'confirmed_at' => $this->confirmed_at?->toISOString(),
            'reversed_at' => $this->reversed_at?->toISOString(),
            'reversal_reference_id' => $this->reversal_reference_id,
            'lines' => $this->whenLoaded('transactions', fn () => $this->transactions
                ->map(fn ($transaction) => [
                    'id' => $transaction->id,
                    'uuid' => $transaction->uuid,
                    'account_code' => $transaction->relationLoaded('account') && $transaction->account
                        ? $transaction->account->account_code
                        : null,
                    'account' => $transaction->relationLoaded('account') && $transaction->account ? [
                        'id' => $transaction->account->id,
                        'account_code' => $transaction->account->account_code,
                        'account_name' => $transaction->account->account_name,
                    ] : null,
                    'transaction_type' => $transaction->transaction_type,
                    'debit' => $transaction->transaction_type === 'debit' ? (float) $transaction->amount : 0,
                    'credit' => $transaction->transaction_type === 'credit' ? (float) $transaction->amount : 0,
                    'amount' => (float) $transaction->amount,
                    'currency_id' => $transaction->currency_id,
                    'currency' => $transaction->relationLoaded('currency') && $transaction->currency ? [
                        'id' => $transaction->currency->id,
                        'code' => $transaction->currency->code,
                        'name' => $transaction->currency->name,
                    ] : null,
                    'exchange_rate' => $transaction->exchange_rate !== null ? (float) $transaction->exchange_rate : null,
                    'base_amount' => $transaction->base_amount !== null ? (float) $transaction->base_amount : null,
                    'description' => $transaction->description,
                    'created_at' => $transaction->created_at?->toISOString(),
                ])
                ->all()),
        ];
    }
}
