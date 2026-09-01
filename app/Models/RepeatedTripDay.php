<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RepeatedTripDay extends Model
{
    use HasFactory;

    protected $fillable = [
        'repeat_id',
        'day_of_week',
    ];

    protected $casts = [
        'day_of_week' => 'string',
    ];

    public function tripRepeat()
    {
        return $this->belongsTo(TripRepeat::class, 'repeat_id');
    }
}
