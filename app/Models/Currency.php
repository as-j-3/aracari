<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'symbol',
        'price_to_yr',
        'code',
        'rate_source',
        'last_rate_update',
    ];

    protected $casts = [
        'price_to_yr' => 'decimal:4',
        'last_rate_update' => 'datetime',
    ];
    
    /**
     * Relationship: Currency has many bookings
     */
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Relationship: Currency has many transactions
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Relationship: Currency has many trips
     */
    public function trips()
    {
        return $this->hasMany(Trip::class);
    }
}
