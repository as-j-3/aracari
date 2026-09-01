<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxRule extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tax_id',
        'country_id',
        'provider_id',
        'service_type_id',
        'payment_method_id',
        'trip_id',
        'min_amount',
        'max_amount',
        'currency_id',
        'is_active',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
    ];

    /** Get the tax associated with the tax rule.
     */
    public function tax()
    {
        return $this->belongsTo(Tax::class);
    }

    /** Get the country associated with the tax rule.
     */
    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    /** Get the provider associated with the tax rule.
     */
    public function provider()
    {
        return $this->belongsTo(ProviderService::class, 'provider_id');
    }

    /** Get the service type associated with the tax rule.
     */
    public function serviceType()
    {
        return $this->belongsTo(TypeOfService::class, 'service_type_id');
    }

    /** Get the payment method associated with the tax rule.
     */
    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    /** Get the trip associated with the tax rule.
     */
    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    /** Get the user that created the tax rule.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_user');
    }
    
    /**
     * Relationship: TaxRule belongs to a currency
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    /** Scope a query to only include active tax rules.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /** Scope a query to only include tax rules for a specific country.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $countryId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForCountry($query, $countryId)
    {
        return $query->where('country_id', $countryId);
    }

    /** Scope a query to only include tax rules for a specific provider.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $providerId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForProvider($query, $providerId)
    {
        return $query->where('provider_id', $providerId);
    }

    /** Scope a query to only include tax rules for a specific trip.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $tripId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForTrip($query, $tripId)
    {
        return $query->where('trip_id', $tripId);
    }
}