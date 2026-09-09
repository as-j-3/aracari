<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FinalAccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'final_account_id' => $this->final_account_id,
            'name'             => $this->name,
            'accounts_count'   => $this->whenCounted('accounts'),
            'created_at'       => $this->created_at?->toISOString(),
            'updated_at'       => $this->updated_at?->toISOString(),
        ];
    }
}
