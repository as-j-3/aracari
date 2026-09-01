<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CouponTrip extends Model
{
    use HasFactory;

    protected $table = 'coupon_trips';

    protected $fillable = [
        'coupon_id',
        'trip_id',
    ];

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }
}
