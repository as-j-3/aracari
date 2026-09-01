<?php

namespace App\Models;

use App\Constants\TripSeatStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TripSeat extends Model
{
    use HasFactory;

    protected $table = 'trip_seats';

    protected $fillable = [
        'trip_id',
        'seat_id',
        'trip_seat_status_id',
        'booking_id',
        'user_id',
        'reserved_until',
    ];

    protected $casts = [
        'reserved_until' => 'datetime',
    ];

    public function trip()
    {
        return $this->belongsTo(Trip::class, 'trip_id');
    }

    public function seat()
    {
        return $this->belongsTo(Seat::class, 'seat_id');
    }

    public function status()
    {
        return $this->belongsTo(TripSeatStatus::class, 'trip_seat_status_id');
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * فحص هل الـ Hold ما يزال صالحاً (غير منتهٍ)
     */
    public function isHoldValid(): bool
    {
        return (int) $this->trip_seat_status_id === TripSeatStatus::HELD
            && $this->reserved_until !== null
            && $this->reserved_until->isFuture();
    }
}
