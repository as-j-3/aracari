<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TripPolicy extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_id',
        'policy_id',
    ];

    /**
     * Get the trip for the trip policy.
     */
    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    /**
     * Get the policy for the trip policy.
     */
    public function policy()
    {
        return $this->belongsTo(Policy::class);
    }
}
