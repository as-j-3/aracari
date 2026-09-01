<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TripAmenity extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_id',
        'amenity_id',
    ];

    /**
     * Get the trip for the trip amenity.
     */
    public function trip()
    {
        return $this->belongsTo(Trip::class, 'trip_id', 'id');
    }

    /**
     * Get the amenity for the trip amenity.
     */
    public function amenity()
    {
        return $this->belongsTo(Amenity::class);
    }
}
