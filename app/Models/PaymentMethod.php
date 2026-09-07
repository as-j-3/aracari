<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use HasFactory;


    protected $fillable = [
        'method_name',
        'provider_id',
        'country_id',
        'currency_id',
    ];

    /**
     * Get the booking payments for the payment method.
     */
    public function bookingPayments()
    {
        return $this->hasMany(BookingPayment::class);
    }

    public function provider()
    {
        return $this->belongsTo(ProviderService::class);
    }

    /* Get the country associated with the payment method.
     */
    public function country()
    {
        return $this->belongsTo(Country::class);
    }

     /**
     * Relationship: PaymentMethod has many tax rules
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function taxRules()
    {
        return $this->hasMany(TaxRule::class);
    }
    
    /**
     * Relationship: PaymentMethod has many tax rules
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function commissionPaymentMethods()
    {
        return $this->hasMany(CommissionPaymentMethod::class);
    }
    public function directTransferPayments()
{
    return $this->hasMany(
        DirectTransferPayment::class,
        'payment_method_id'
    );
}
}
