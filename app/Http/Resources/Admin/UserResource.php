<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'phone_verified_at' => $this->phone_verified_at?->toIso8601String(),
            'is_approved' => (bool) $this->is_approved,
            'type_user_code' => $this->type_user_code,
            'user_type' => $this->whenLoaded('userType', function () {
                return [
                    'id' => $this->userType?->id,
                    'type_name' => $this->userType?->type_name,
                    'type_user_code' => $this->userType?->type_user_code,
                ];
            }),
            'status_id' => $this->status_id,
            'status' => $this->whenLoaded('status', function () {
                return [
                    'id' => $this->status?->id,
                    'status_code' => $this->status?->status_code,
                    'status_name' => $this->status?->status_name,
                ];
            }),
            'user_parent' => $this->user_parent,
            'parent' => $this->whenLoaded('parent', function () {
                return [
                    'id' => $this->parent?->id,
                    'uuid' => $this->parent?->uuid,
                    'name' => $this->parent?->name,
                ];
            }),
            'roles' => $this->whenLoaded('roles', function () {
                return $this->roles->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                    ];
                });
            }),
            'provider_service' => $this->whenLoaded('providerService', function () {
                return [
                    'id' => $this->providerService?->id,
                    'service_name' => $this->providerService?->service_name ?? null,
                ];
            }),
            'is_admin' => $this->isAdmin(),
            'is_provider' => $this->isProvider(),
            'is_customer' => $this->isCustomer(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
