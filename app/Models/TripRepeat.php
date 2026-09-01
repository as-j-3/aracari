<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TripRepeat extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_id',
        'start_date',
        'end_date',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'status' => 'string',
    ];

    public function trip()
    {
        return $this->belongsTo(Trip::class, 'trip_id', 'id');
    }

    public function repeatedDays()
    {
        return $this->hasMany(RepeatedTripDay::class, 'repeat_id');
    }
}
