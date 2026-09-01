<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecurityIncident extends Model
{
    protected $fillable = [
        'incident_type',
        'severity',
        'ip_address',
        'user_id',
        'user_agent',
        'request_method',
        'request_path',
        'request_inputs',
        'attempt_count',
        'was_blocked',
        'occurred_at',
    ];

    protected $casts = [
        'request_inputs' => 'array',
        'was_blocked' => 'boolean',
        'occurred_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}