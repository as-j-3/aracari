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
            'type_account'   => $this->normal_account,
            'account_nature' => $this->account_nature,

            // الحساب الأب
            'parent_id'      => $this->parent_id,
            'parent' => $this->whenLoaded('parent', fn () => $this->parent ? [
                'id'           => $this->parent->id,
                'uuid'         => $this->parent->uuid,
                'account_code' => $this->parent->account_code,
                'account_name' => $this->parent->account_name,
                'account_type' => $this->parent->account_type,
                'type_account' => $this->parent->normal_account,
            ] : null),

            // الحسابات الفرعية
            'children' => $this->whenLoaded('children', fn () => $this->children
                ->map(fn ($child) => [
                    'id'             => $child->id,
                    'uuid'           => $child->uuid,
                    'account_code'   => $child->account_code,
                    'account_name'   => $child->account_name,
                    'account_type'   => $child->account_type,
                    'type_account'   => $child->normal_account,
                    'account_nature' => $child->account_nature,
                ])
                ->all()),

            // الحساب الختامي
            'final_account' => $this->whenLoaded('finalAccount', fn () => $this->finalAccount ? [
                'id'   => $this->finalAccount->final_account_id,
                'name' => $this->finalAccount->name,
            ] : null),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
