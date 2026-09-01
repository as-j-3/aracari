<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingTax extends Model
{
    use HasFactory;

    protected $table = 'booking_taxes';

    protected $fillable = [
        'booking_id',
        'tax_id',
        'tax_name',
        'tax_type_rate_or_amount',
        'tax_rate',
        'tax_amount',
        'currency_id',
        'exchange_rate',
        'base_tax_amount',
    ];

    protected $casts = [
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
        'base_tax_amount' => 'decimal:2',
    ];

     /**
     * Relationship: BookingTax belongs to a booking
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
     public function booking()
     {
         return $this->belongsTo(Booking::class);
     }


    public function tax()
    {
        return $this->belongsTo(Tax::class);
    }
    
    
    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }
}
