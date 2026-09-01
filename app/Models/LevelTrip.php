<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LevelTrip extends Model
{
    use HasFactory;
    
    
    protected $fillable = [
        'level_name',
    ];

    /**
     * Get the trips for the level trip.
     */
    public function trips()
    {
        return $this->hasMany(Trip::class, 'level_trip_id', 'id');
    }
}
