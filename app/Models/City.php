<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'country_id',
        'name',
        'image',
    ];

    protected $casts = [
        'uuid' => 'string',
    ];

    /**
     * Get the route key for the model (UUID instead of ID)
     *
     * This allows Route Model Binding to use UUID instead of the default ID
     *
     * @return string
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? asset('storage/' . $this->image) : null;
    }

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($city) {
            if (empty($city->uuid)) {
                $city->uuid = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    /**
     * Get the country for the city.
     */
    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Get the trips departing from the city.
     */
    public function departingTrips()
    {
        return $this->hasMany(Trip::class, 'departure_city', 'id');
    }

    /**
     * Get the trips arriving to the city.
     */
    public function arrivingTrips()
    {
        return $this->hasMany(Trip::class, 'destination_city', 'id');
    }
    public function stations()
    {
        return $this->hasMany(CityStation::class);
    }
    /**
 * نقاط الانطلاق المميزة من هذه المدينة
 */
    public function getPickupPointsAttribute(): array
    {
        return Trip::where('departure_city', $this->id)
            ->where('is_public', true)
            ->where('departure_time', '>=', now())
            ->whereNotIn('trip_status_id', [\App\Constants\TripStatus::CANCELLED])
            ->whereNotNull('pickup_location_id')
            ->with('departureStation:id,name')
            ->get()
            ->pluck('departureStation.name')
            ->filter()
            ->unique()
            ->take(5)
            ->values()
            ->toArray();
    }
}
