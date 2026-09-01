<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DigitalPocket extends Model
{
    use HasFactory;

    protected $fillable = [
        'pocket_name',
        'currency_id',
    ];

    /**
     * Get the receive revenue methods for the digital pocket.
     */
    public function receiveRevenueMethods()
    {
        return $this->hasMany(ReceiveRevenueMethod::class);
    }

    /**
     * Get the currency associated with the digital pocket.
     */
    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }
}
