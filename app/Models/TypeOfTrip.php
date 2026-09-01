<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TypeOfTrip extends Model
{
    use HasFactory;

    protected $fillable = [
        'type_name',
    ];

    /**
     * Get the trips for the type of trip.
     */
    public function trips()
    {
        return $this->hasMany(Trip::class, 'type_of_trip_id', 'id');
    }
}
