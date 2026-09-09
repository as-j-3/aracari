<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'uuid'           => $this->uuid,
            'account_code'   => $this->account_code,
            'account_name'   => $this->account_name,
            'type_account'   => (int) $this->type_account, // 0: Main (رئيسي), 1: Sub (فرعي)
            'account_nature' => $this->account_nature,     // debit / credit

            // الحساب الأب
            'parent_id'      => $this->parent_id,
            'parent'         => $this->whenLoaded('parent', fn () => $this->parent ? [
                'id'             => $this->parent->id,
                'uuid'           => $this->parent->uuid,
                'account_code'   => $this->parent->account_code,
                'account_name'   => $this->parent->account_name,
                'type_account'   => (int) $this->parent->type_account,
                'account_nature' => $this->parent->account_nature,
            ] : null),

            // الحسابات الفرعية
            'children'       => $this->whenLoaded('children', fn () => $this->children
                ->map(fn ($child) => [
                    'id'             => $child->id,
                    'uuid'           => $child->uuid,
                    'account_code'   => $child->account_code,
                    'account_name'   => $child->account_name,
                    'type_account'   => (int) $child->type_account,
                    'account_nature' => $child->account_nature,
                ])
                ->all()),

            // الحساب الختامي
            'final_account_id' => $this->final_account_id,
            'final_account'    => $this->whenLoaded('finalAccount', fn () => $this->finalAccount ? [
                'final_account_id' => $this->finalAccount->final_account_id,
                'name'             => $this->finalAccount->name,
            ] : null),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}