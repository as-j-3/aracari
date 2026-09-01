<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str; 
use App\Traits\BelongsToProvider;
use App\Traits\HasAudit;
use App\Services\CurrencyService;
use App\Models\BookingTax;
use App\Constants\BookingStatus as BookingStatusConst;
use App\Models\BookingStatus as BookingStatusModel;

class Booking extends Model
{
    use BelongsToProvider, HasAudit, HasFactory, SoftDeletes;

    protected string $providerOwnershipRelation = 'branch.provider';

    protected $table = 'bookings';

    protected $fillable = [
        'uuid',
        'user_id',
        'branch_id',
        'booking_status_id',
        'amount',
        'currency_id',
        'exchange_rate',
        'base_amount',
        'commission_id',
        'commission_value',
        'commission_amount',
        'commission_type_fixed_or_percentage', 
        'note',
        'cancelled_at',
    ];

    protected $casts = [
        'uuid' => 'string',
        'amount' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
        'base_amount' => 'decimal:2',
        'commission_value' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'cancelled_at' => 'datetime',
        'commission_type_fixed_or_percentage' => 'string', 
    ];


    public function getRouteKeyName(): string
    {
        return 'uuid';
    }


    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }


    public function user()
    {
        return $this->belongsTo(User::class, 'user_id')->withDefault([
            'name' => 'مستخدم محذوف',
            'email' => 'deleted@example.com',
        ]);
    }

    public function branch()
    {
        return $this->belongsTo(ProviderBranch::class, 'branch_id')->withDefault([
            'address' => 'فرع محذوف',
            'phone_number' => 'غير محدد',
        ]);
    }

    public function commission()
    {
        return $this->belongsTo(Commission::class, 'commission_id')->withDefault([
            'value' => 0,
            'amount' => 0,
        ]);
    }

    /**
     * Relationship: Booking has one payment record
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function payment()
    {
        return $this->hasOne(BookingPayment::class, 'booking_id')->withDefault([
            'payed_amount' => 0,
            'payment_method_id' => null,
        ]);
    }

    /**
     * Relationship: Booking has a status
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function status()
    {
        return $this->belongsTo(BookingStatusModel::class, 'booking_status_id')->withDefault([
            'status_name' => 'غير محدد',
        ]);
    }

    /**
     * Relationship: Booking belongs to a currency
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }
    
    /**
     * Relationship: Booking has many trip reservations
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tripReservations()
    {
        return $this->hasMany(TripReservation::class);
    }

    /**
     * Relationship: Booking has one main trip (through first trip reservation)
     *  Added for dashboard statistics query support
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasOneThrough
     */
    public function trip()
    {
        return $this->hasOneThrough(Trip::class, TripReservation::class, 'booking_id', 'id', 'id', 'trip_id');
    }

    /**
     * Relationship: Booking has many tax records
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function taxes()
    {
        return $this->hasMany(BookingTax::class);
    }

    /**
     * Accessor: Get formatted amount with thousands separator
     * 
     * @return string Formatted amount
     */
    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 2, '.', ',');
    }

    /**
     * Accessor: Get formatted commission amount with thousands separator
     * 
     * @return string Formatted commission amount
     */
    public function getFormattedCommissionAmountAttribute(): string
    {
        return number_format($this->commission_amount ?? 0, 2, '.', ',');
    }

    /**
     * Accessor: Check if booking is fully paid
     * 
     * @return bool True if paid amount equals or exceeds booking amount
     */
    public function getIsPaidAttribute(): bool
    {
        return $this->payment && $this->payment->payed_amount >= $this->amount;
    }

    /**
     * Accessor: Check if booking is cancelled
     * 
     * @return bool True if booking status is cancelled or cancellation timestamp exists
     */
    public function getIsCancelledAttribute(): bool
    {
        return $this->booking_status_id === BookingStatusConst::CANCELLED || !is_null($this->cancelled_at);
    }

    /**
     * Accessor: Check if booking can be cancelled
     * 
     * Business logic:
     * - Cannot cancel if already cancelled
     * - Cannot cancel if any trip has already departed
     * 
     * @return bool True if booking can be cancelled
     */
    public function getCanCancelAttribute(): bool
    {
        if ($this->is_cancelled) {
            return false;
        }

        // التحقق من أن جميع الرحلات لم تبدأ بعد
        foreach ($this->tripReservations as $reservation) {
            if ($reservation->trip->departure_time <= now()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Accessor: Get amount converted to USD
     * 
     * @return float Amount in USD
     */
    public function getInUsdAttribute(): float
    {
        return app(CurrencyService::class)->convert($this->amount, 'YER', 'USD');
    }

    /**
     * Accessor: Get amount converted to EUR
     * 
     * @return float Amount in EUR
     */
    public function getInEurAttribute(): float
    {
        return app(CurrencyService::class)->convert($this->amount, 'YER', 'EUR');
    }

    /**
     * Accessor: Get amount converted to SAR
     * 
     * @return float Amount in SAR
     */
    public function getInSarAttribute(): float
    {
        return app(CurrencyService::class)->convert($this->amount, 'YER', 'SAR');
    }

    /**
     * Accessor: Get amount converted to all supported currencies
     * 
     * @return array Amount in YER, USD, EUR, and SAR
     */
    public function getInAllCurrenciesAttribute(): array
    {
        $converter = app(CurrencyService::class);
        
        return [
            'YER' => $this->amount,
            'USD' => $converter->convert($this->amount, 'YER', 'USD'),
            'EUR' => $converter->convert($this->amount, 'YER', 'EUR'),
            'SAR' => $converter->convert($this->amount, 'YER', 'SAR')
        ];
    }

    /**
     * Relationship: Booking may have coupon usage records
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function couponUsages()
    {
        return $this->hasMany(CouponUsage::class);
    }

    /**
     * Relationship: Booking has many tickets through trip reservations
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function tickets()
    {
        return $this->hasManyThrough(Ticket::class, TripReservation::class);
    }

    /**
     * Scope: Filter bookings by branch
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $branchId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * Scope: Filter bookings accessible by user
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \App\Models\User $user
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAccessibleByUser($query, \App\Models\User $user)
    {
        if ($user->isProviderOwner()) {
            // Provider owner sees all bookings from their branches
            return $query;
        }

        // Staff sees only bookings from their accessible branches
        $branchIds = $user->getAccessibleBranches()->pluck('id');
        return $query->whereIn('branch_id', $branchIds);
    }
}
