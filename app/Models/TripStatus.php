<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TripStatus extends Model
{
    use HasFactory;

    
    protected $fillable = [
        'status_name',
    ];

    /**
     * Get the trips for the trip status.
     */
    public function trips()
    {
        return $this->hasMany(Trip::class, 'trip_status_id', 'id');
    }
}
