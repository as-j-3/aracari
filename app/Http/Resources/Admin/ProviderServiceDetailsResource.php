<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProviderServiceDetailsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return array_merge(
            (new ProviderServiceResource($this->resource))->toArray($request),

            [
                'description' => $this->description,
                'cr_number' => $this->cr_number,
                'contact_phone' => $this->contact_phone,
                'contact_email' => $this->contact_email,
                'website' => $this->website,
                'commission_type' => $this->commission_type,

                'user' => $this->user ? [
                    'name' => $this->user->name,
                    'phone' => $this->user->phone_number,
                    'email' => $this->user->email,
                    'created_at' => $this->user->created_at?->format('Y-m-d'),
                    
                ] : null,

                'receive_revenue_methods' => $this->receiveRevenueMethods->map(function ($method) {
                    return [
                        'id' => $method->id,
                        'account_number' => $method->account_number,
                        'account_name' => $method->account_name,

                        'digital_pocket' => $method->digitalPocket ? [
                            'id' => $method->digitalPocket->id,
                            'pocket_name' => $method->digitalPocket->pocket_name,

                            'currency' => $method->digitalPocket->currency ? [
                                'name' => $method->digitalPocket->currency->name,
                            ] : null,
                        ] : null,
                    ];
                }),

                'branches' => $this->branches,
                
            ]
        );
    }
}