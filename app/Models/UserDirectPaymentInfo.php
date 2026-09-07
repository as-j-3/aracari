<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDirectPaymentInfo extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'direct_transfer_payment_id',
        'booking_payment_id',
        'receipt_number',
        'sender_name',
    ];

    /*
    |--------------------------------------------------------------------------
    | Direct Transfer Payment
    |--------------------------------------------------------------------------
    */

    public function directTransferPayment(): BelongsTo
    {
        return $this->belongsTo(
            DirectTransferPayment::class,
            'direct_transfer_payment_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Booking Payment
    |--------------------------------------------------------------------------
    */

    public function bookingPayment(): BelongsTo
    {
        return $this->belongsTo(
            BookingPayment::class,
            'booking_payment_id'
        );
    }
}