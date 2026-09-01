<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CouponStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'status_code',
        'name',
    ];

    /**
     * Get the coupons for the status.
     */
    public function coupons()
    {
        return $this->hasMany(Coupon::class, 'status_id');
    }
}
