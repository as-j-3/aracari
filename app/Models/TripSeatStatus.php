<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TripSeatStatus extends Model
{
    use HasFactory;

    protected $table = 'trip_seat_statuses';

    protected $fillable = [
        'status_name',
    ];

    public function tripSeats()
    {
        return $this->hasMany(TripSeat::class, 'trip_seat_status_id');
    }
}
