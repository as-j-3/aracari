<?php

namespace App\Models;

use App\Constants\BookingStatus as BookingStatusConst;
use App\Constants\CouponStatus as CouponStatusConst;
use App\Constants\TripStatus as TripStatusConst;
use App\Constants\TripSeatStatus;
use App\Models\TripStatus as TripStatusModel;
use App\Services\CurrencyService;
use App\Traits\BelongsToProvider;
use App\Traits\HasAudit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Trip extends Model
{
    use BelongsToProvider, HasAudit, HasFactory, SoftDeletes;

    protected string $providerOwnershipColumn = 'created_by_user';
    protected string $providerOwnershipScope = 'provider_user';

    protected $table = 'trips';

    protected $fillable = [
        'uuid',
        'created_by_user',
        'service_id',
        'vehicle_id',
        'type_of_trip_id',
        'trip_name_id',
        'is_public',
        'departure_city',
        'destination_city',
        'pickup_location',
        'pickup_location_id',
        'arrival_location_id',
        'check_in_time',
        'departure_time',
        'arrival_time',
        'trip_status_id',
        'price_for_adult',
        'price_for_child',
        'currency_id',
        'discount_percentage',
        'conditions',
        'level_trip_id',
        'parent_id',
        'ticket_limit',
        'available_seats',
        'image',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'check_in_time' => 'datetime',
        'departure_time' => 'datetime',
        'arrival_time' => 'datetime',
        'price_for_adult' => 'decimal:2',
        'price_for_child' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'available_seats' => 'integer',
        'ticket_limit' => 'integer',
        'image' => 'string',
    ];

    /**
     * Model boot method for automatic UUID generation
     *
     * This method automatically generates a UUID for new trips
     * if not provided during creation.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $trip): void {
            if (empty($trip->uuid)) {
                $trip->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Scope: Only trips that belong to the given provider
     */
    public function scopeForProvider($query, int $providerId)
    {
        return $query->where('created_by_user', $providerId);
    }

    /**
     * Scope: Only trips that are available (not departed, not cancelled)
     */
    public function scopeAvailable($query)
    {
        return $query->where('departure_time', '>', now())
            ->whereHas('tripStatus', fn($q) => $q->where('status_name', '!=', 'cancelled'));
    }

    /**
     * Scope: Only trips that are public (for frontend display)
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }
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

    /**
     * Generate unique trip name ID
     * 
     * This method generates a unique trip identifier with provider prefix
     * and random number, ensuring no collisions with existing trips.
     * 
     * Business logic:
     * - Uses provider prefix or default 'TR'
     * - Generates 4-digit random number
     * - Ensures uniqueness by checking existing trips
     * 
     * @param string|null $providerPrefix Provider-specific prefix
     * @return string Unique trip name ID
     */
    public static function generateTripNameId($providerPrefix = null): string
    {
        $providerPrefix = $providerPrefix ?: 'TR';
        $prefix = strtoupper(substr((string) $providerPrefix, 0, 2));

        do {
            $tripId = $prefix . str_pad((string) mt_rand(1, 999999), 4, '0', STR_PAD_LEFT);
        } while (self::where('trip_name_id', $tripId)->exists());

        return $tripId;
    }

    /**
     * Relationship: Trip belongs to a user (creator)
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'created_by_user');
    }
    
    /**
     * Relationship: Trip belongs to a currency
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }


    /**
     * Relationship: Trip belongs to a service
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    /**
     * Relationship: Trip may have a parent trip (for recurring trips)
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parentTrip()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Relationship: Trip may have child trips (for recurring trips)
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function childTrips()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Relationship: Trip has many policies
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function policies()
    {
        return $this->belongsToMany(Policy::class, 'trip_policies');
    }

    /**
     * Relationship: Trip belongs to a vehicle
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }
    
    public function departureStation()
    {
        return $this->belongsTo(CityStation::class, 'pickup_location_id');
    }

    public function arrivalStation()
    {
        return $this->belongsTo(CityStation::class, 'arrival_location_id');
    }

    /**
     * Relationship: Trip belongs to a trip type
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function typeOfTrip()
    {
        return $this->belongsTo(TypeOfTrip::class, 'type_of_trip_id');
    }

    /**
     * Relationship: Trip belongs to a trip level
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function levelTrip()
    {
        return $this->belongsTo(LevelTrip::class, 'level_trip_id');
    }

    /**
     * Relationship: Trip has a status
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function tripStatus()
    {
        return $this->belongsTo(TripStatusModel::class, 'trip_status_id');
    }

    /**
     * Relationship: Trip belongs to departure city
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function departureCity()
    {
        return $this->belongsTo(City::class, 'departure_city');
    }

    /**
     * Relationship: Trip belongs to destination city
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function destinationCity()
    {
        return $this->belongsTo(City::class, 'destination_city');
    }

    /**
     * Relationship: Trip has many trip reservations
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tripReservations()
    {
        return $this->hasMany(TripReservation::class, 'trip_id');
    }

    /**
     * Relationship: Trip has many confirmed trip reservations
     * 
     * Business logic:
     * - Filters for active reservations
     * - Filters for confirmed bookings only
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function confirmedTripReservations()
    {
        return $this->hasMany(TripReservation::class, 'trip_id')
            ->where('status', 'active')
            ->whereHas('booking', function ($query) {
                $query->where('booking_status_id', BookingStatusConst::CONFIRMED);
            });
    }

    /**
     * علاقة مقاعد الرحلة المخصصة / الممسوكة (trip_seats)
     * تعديل: تمت الإضافة لدعم نظام الحجز المؤقت (Hold)
     */
    public function tripSeats(): HasMany
    {
        return $this->hasMany(TripSeat::class, 'trip_id');
    }

    /**
     * Relationship: Trip has many trip amenity records
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tripAmenities()
    {
        return $this->hasMany(TripAmenity::class, 'trip_id');
    }

    /**
     * Relationship: Trip has many amenities
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function amenities()
    {
        return $this->belongsToMany(Amenity::class, 'trip_amenities', 'trip_id', 'amenity_id');
    }

    /**
     * Relationship: Trip has many applicable coupons
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function coupons()
    {
        return $this->belongsToMany(Coupon::class, 'coupon_trips', 'trip_id', 'coupon_id');
    }

    /**
     * Relationship: Trip has many bookings through trip reservations
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function bookings()
    {
        return $this->belongsToMany(Booking::class, 'trip_reservations', 'trip_id', 'booking_id')
            ->withTimestamps()
            ->distinct();
    }

    public function waypoints(): HasMany
    {
        return $this->hasMany(TripWaypoint::class)->orderBy('id', 'asc');
    }
    /**
     * Accessor: Get highest discount percentage from attached active coupons
     * 
     * Business logic:
     * - Filters for active coupons only
     * - Returns highest percentage among active coupons
     * 
     * @return float Best discount percentage or 0.0
     */
    public function getBestDiscountPercentage(): float
    {
        $bestCoupon = $this->coupons()->where('status_id', CouponStatusConst::ACTIVE)->first();
        
        if ($bestCoupon) {
            return (float) $bestCoupon->percentage;
        }
        
        return 0.0;
    }

    /**
     * Accessor: Get effective discount percentage
     * 
     * Business logic:
     * - Returns higher of trip discount or best coupon discount
     * 
     * @return float Effective discount percentage
     */
    public function getEffectiveDiscountPercentage(): float
    {
        // Use the higher of trip discount or best coupon discount
        return max((float) $this->discount_percentage, $this->getBestDiscountPercentage());
    }

    /**
     * Relationship: Trip has many repeat schedules
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function repeats()
    {
        return $this->hasMany(TripRepeat::class, 'trip_id');
    }
    /**
     * Relationship: Trip has many tax rules
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function taxRules()
    {
        return $this->hasMany(TaxRule::class);
    }

    /**
     * Accessor: Get user ID alias for created_by_user
     * 
     * @return int|null User ID
     */
    public function getUserIdAttribute(): ?int
    {
        return $this->created_by_user;
    }

    /**
     * Mutator: Set user ID alias for created_by_user
     * 
     * @param mixed $value User ID value
     * @return void
     */
    public function setUserIdAttribute($value): void
    {
        $this->attributes['created_by_user'] = $value;
    }

    /**
     * Accessor: Get calculated seat count
     * 
     * Business logic:
     * - Calculates total seats from vehicle
     * - Subtracts booked seat count
     * - Returns 0 if negative
     * 
     * @return int Available seat count
     */
    public function getCalculatedSeatsAttribute(): int
    {
        $totalSeats = (int) ($this->vehicle?->seats_count ?? 0);
        $reservedSeats = count($this->getBookedSeatIds());
        return max(0, $totalSeats - $reservedSeats);
    }

    /**
     * Accessor: Get total seat count from vehicle
     * 
     * @return int Total seat count
     */
    public function getTotalSeatsAttribute(): int
    {
        return (int) ($this->vehicle?->seats_count ?? 0);
    }

    /**
     * Get booked seat IDs for this trip
     * 
     * Business logic:
     * - Retrieves active tickets for this specific trip
     * - Ensures seat protection is trip-specific, not vehicle-specific
     * 
     * @return array Booked seat IDs
     */
    public function getBookedSeatIds(): array
    {
        return $this->tripReservations()
            ->whereHas('booking', fn($q) => $q->where('booking_status_id', BookingStatusConst::CONFIRMED))
            ->with('tickets')
            ->get()
            ->pluck('tickets.*.seat_id')
            ->flatten()
            ->toArray();
    }

    /**
     * Get seats with availability status
     * 
     * Business logic:
     * - Maps vehicle seats with availability status
     * - Marks seats as booked or available
     * - Returns empty collection if no vehicle or seats
     * 
     * @return \Illuminate\Support\Collection Seats with status
     */
    public function getSeatsWithStatus(): \Illuminate\Support\Collection
    {
        // Check if vehicle and seats exist
        if (!$this->vehicle || !$this->vehicle->seats) {
            return collect([]);
        }

        $bookedSeatIds = $this->getBookedSeatIds();

        // تعديل: جلب سجلات trip_seats النشطة للرحلة لتحديد الحالات (held, booked, blocked)
        $tripSeats = $this->tripSeats()
            ->get()
            ->keyBy('seat_id');

        return $this->vehicle->seats->map(function ($seat) use ($bookedSeatIds, $tripSeats) {
            $tripSeat = $tripSeats->get($seat->id);

            $status = 'available';
            $isAvailable = true;
            $reservedUntil = null;
            $heldByUserId = null;

            if (in_array($seat->id, $bookedSeatIds)) {
                $status = 'booked';
                $isAvailable = false;
            } elseif ($tripSeat) {
                if ((int) $tripSeat->trip_seat_status_id === TripSeatStatus::BOOKED) {
                    $status = 'booked';
                    $isAvailable = false;
                } elseif ((int) $tripSeat->trip_seat_status_id === TripSeatStatus::BLOCKED) {
                    $status = 'blocked';
                    $isAvailable = false;
                } elseif ((int) $tripSeat->trip_seat_status_id === TripSeatStatus::HELD) {
                    // فحص هل الـ Hold ما يزال صالحاً (لم تتجاوز 5 دقائق)
                    if ($tripSeat->reserved_until && $tripSeat->reserved_until->isFuture()) {
                        $status = 'held';
                        $isAvailable = false;
                        $reservedUntil = $tripSeat->reserved_until->toIso8601String();
                        $heldByUserId = $tripSeat->user_id;
                    }
                    // ملاحظة: إذا كان الـ Hold منتهياً (reserved_until <= now())، يُعتبر المقعد متاحاً تلقائياً
                }
            }

            return [
                'id' => $seat->id,
                'seat_id' => $seat->id,
                'seat_name' => $seat->seat_name,
                'is_available' => $isAvailable,
                'status' => $status,
                'reserved_until' => $reservedUntil,
                'held_by_user_id' => $heldByUserId,
            ];
        });
    }

    /**
     * Accessor: Get currency code for trip prices
     * 
     * Business logic:
     * - Returns trip currency if defined
     * - Defaults to YER (Yemeni Rial)
     * 
     * @return string Currency code
     */
    public function getCurrencyCodeAttribute(): string
    {
        // إذا كانت هناك عملة محددة، استخدمها
        if ($this->currency_id && $this->currency) {
            return $this->currency->symbol ?? 'YER';
        }
        
        // بشكل افتراضي، استخدم الريال اليمني
        return 'ر.ي';
    }

    /**
     * Accessor: Get currency name for trip prices
     * 
     * Business logic:
     * - Returns trip currency name if defined
     * - Defaults to 'ريال يمني'
     * 
     * @return string Currency name
     */
    public function getCurrencyNameAttribute(): string
    {
        if ($this->currency_id && $this->currency) {
            return $this->currency->name ?? 'ريال يمني';
        }
        
        return 'ريال يمني';
    }

    /**
     * Accessor: Get formatted adult price with currency
     * 
     * @return string Formatted price with currency
     */
    public function getAdultPriceFormattedAttribute(): string
    {
        $currency = $this->currency_code;
        $price = number_format($this->price_for_adult, 2);
        
        return "$price $currency";
    }

    /**
     * Accessor: Get formatted child price with currency
     * 
     * @return string Formatted price with currency
     */
    public function getChildPriceFormattedAttribute(): string
    {
        $currency = $this->currency_code;
        $price = number_format($this->price_for_child, 2);
        
        return "$price $currency";
    }

    /**
 * Accessor: Get prices in all supported currencies with caching
 * 
 * Business logic:
 * - Caches for 1 hour to improve performance
 * - Uses exchange_rates with fallback to price_to_yr
 * - Includes exchange rate information
 * 
 * @return array Prices in all currencies with exchange rates
 */
public function getPricesInAllCurrenciesAttribute(): array
{
    $cacheKey = "trip_prices_{$this->id}_" . now()->format('Y-m-d_H');
    return cache()->remember($cacheKey, 3600, function () {
        $currencyService = app(\App\Services\CurrencyService::class);
        $baseCurrency = $currencyService->baseCurrency();
        
        if (!$baseCurrency) {
            return [];
        }
        
        $adultPrice = $this->price_for_adult;
        $childPrice = $this->price_for_child;
        
        $currencies = \App\Models\Currency::all();
        $result = [];
        
        foreach ($currencies as $currency) {
            //  استخدم exchange_rates الجديد
            $rate = $currencyService->getLatestExchangeRate($baseCurrency->id, $currency->id);
            
            $result[$currency->code] = [
                'adult' => round($adultPrice * $rate, 2),
                'child' => round($childPrice * $rate, 2),
                'currency' => $currency->code,
                'exchange_rate' => $rate,
                'is_base' => $currency->id === $baseCurrency->id,
                'exchange_info' => $currency->id === $baseCurrency->id 
                    ? 'العملة الأساسية' 
                    : "1 {$baseCurrency->code} = {$rate} {$currency->code}",
            ];
        }
        
        return $result;
    });
}

    /**
     * Accessor: Get adult price converted to USD
     * 
     * @return float Adult price in USD
     */
    public function getAdultPriceInUsdAttribute(): float
    {
        $converter = app(CurrencyService::class);
        return $converter->convert($this->price_for_adult, 'YER', 'USD');
    }

    /**
     * Accessor: Get adult price converted to EUR
     * 
     * @return float Adult price in EUR
     */
    public function getAdultPriceInEurAttribute(): float
    {
        $converter = app(CurrencyService::class);
        return $converter->convert($this->price_for_adult, 'YER', 'EUR');
    }

    /**
     * Accessor: Get adult price converted to SAR
     * 
     * @return float Adult price in SAR
     */
    public function getAdultPriceInSarAttribute(): float
    {
        $converter = app(CurrencyService::class);
        return $converter->convert($this->price_for_adult, 'YER', 'SAR');
    }

    /**
     * Accessor: Get child price converted to USD
     * 
     * @return float Child price in USD
     */
    public function getChildPriceInUsdAttribute(): float
    {
        $converter = app(CurrencyService::class);
        return $converter->convert($this->price_for_child, 'YER', 'USD');
    }

    /**
     * Accessor: Get child price converted to EUR
     * 
     * @return float Child price in EUR
     */
    public function getChildPriceInEurAttribute(): float
    {
        $converter = app(CurrencyService::class);
        return $converter->convert($this->price_for_child, 'YER', 'EUR');
    }

    /**
     * Accessor: Get child price converted to SAR
     * 
     * @return float Child price in SAR
     */
    public function getChildPriceInSarAttribute(): float
    {
        $converter = app(CurrencyService::class);
        return $converter->convert($this->price_for_child, 'YER', 'SAR');
    }

    /**
     * Accessor: Get adult prices in all currencies
     * 
     * @return array Adult prices in all supported currencies
     */
    public function getAdultPriceInAllCurrenciesAttribute(): array
    {
        return $this->getPricesInAllCurrenciesAttribute();
    }

    /**
     * Accessor: Get child prices in all currencies
     * 
     * @return array Child prices in all supported currencies
     */
    public function getChildPriceInAllCurrenciesAttribute(): array
    {
        return $this->getPricesInAllCurrenciesAttribute();
    }
        /**
     * Scope: Filter trips with enough available seats
     * 
     * Performance: Uses subquery with EXISTS for optimal performance
     * Security: Uses parameter binding, prevents SQL injection
     * Concurrency: Works with lockForUpdate for atomic operations
     * 
     * @param Builder $query
     * @param int $requiredSeats Number of seats needed
     * @return Builder
     */

    public function scopeHasAvailableSeats(Builder $query, int $requiredSeats): Builder
    {
        if ($requiredSeats <= 0) {
            return $query;
        }

        return $query->whereRaw(
            'COALESCE(trips.ticket_limit, (SELECT seats_count FROM vehicles WHERE vehicles.id = trips.vehicle_id)) >= COALESCE((
                SELECT COUNT(*)
                FROM tickets
                INNER JOIN trip_reservations ON trip_reservations.id = tickets.trip_reservation_id
                WHERE trip_reservations.trip_id = trips.id
                AND tickets.status = ?
                AND trip_reservations.status = ?
                AND trip_reservations.booking_id IN (
                    SELECT id FROM bookings WHERE booking_status_id = ?
                )
            ), 0) + ?',
            ['active', 'active', BookingStatusConst::CONFIRMED, $requiredSeats]
        );
    }

    /**
     * Get available seats count for this trip (with caching)
     * 
     * Performance: Caches result for 5 minutes
     * Concurrency: Safe for read operations
     * 
     * @return int
     */
    public function getAvailableSeatsCountCached(): int
    {
        $cacheKey = "trip_available_seats_{$this->id}";
        
        return cache()->remember($cacheKey, 300, function () {
            return $this->available_seats ?? $this->getCalculatedSeatsAttribute();
        });
    }

    /**
     * Clear seats cache after booking/cancellation
     */
    public function clearSeatsCache(): void
    {
        cache()->forget("trip_available_seats_{$this->id}");
    }
    /**
 * Accessor: Get full image URL
 */
    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? asset('storage/' . $this->image) : null;
    }
}
