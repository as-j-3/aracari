<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SecurityIncidentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'incident_type'  => $this->incident_type,
            'severity'       => $this->severity,
            'ip_address'     => $this->ip_address,
            'user_id'        => $this->user_id,
            'user_agent'     => $this->user_agent,
            'request_method' => $this->request_method,
            'request_path'   => $this->request_path,
            'request_inputs' => $this->request_inputs,
            'attempt_count'  => $this->attempt_count,
            'was_blocked'    => $this->was_blocked,
            'occurred_at'    => $this->occurred_at?->toISOString(),
            'created_at'     => $this->created_at?->toISOString(),
            'updated_at'     => $this->updated_at?->toISOString(),
        ];
    }
}
