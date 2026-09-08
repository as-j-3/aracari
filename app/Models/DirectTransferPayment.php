<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DirectTransferPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'payment_method_id',
        'name',
        'phone_number',
        'bank_name',
        'bank_account_number',
    ];

    /*
    |--------------------------------------------------------------------------
    | Payment Method
    |--------------------------------------------------------------------------
    */

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(
            PaymentMethod::class,
            'payment_method_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | User Direct Payment Infos
    |--------------------------------------------------------------------------
    */

    public function userDirectPaymentInfos(): HasMany
    {
        return $this->hasMany(
            UserDirectPaymentInfo::class,
            'direct_transfer_payment_id'
        );
    }
}