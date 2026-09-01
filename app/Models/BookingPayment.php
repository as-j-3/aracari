<?php

namespace App\Models;

use App\Traits\BelongsToProvider;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Booking;
use App\Models\PaymentMethod as PaymentMethodModel;
use Illuminate\Support\Str;

class BookingPayment extends Model
{
    use BelongsToProvider, HasFactory;

    protected string $providerOwnershipRelation = 'booking.branch.provider';

    protected $table = 'booking_payments';

    protected $fillable = [
        'uuid',
        'payment_method_id',
        'user_id',
        'booking_id',
        'payed_amount',
        'currency_id',
        'exchange_rate',
        'base_amount',
        'note',
    ];

    protected $casts = [
        'uuid' => 'string',
        'payed_amount' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
        'base_amount' => 'decimal:2',
    ];

    /**
     * Get the route key name for URL generation.
     */
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

    /**
     * Relationship: BookingPayment belongs to a payment method
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethodModel::class, 'payment_method_id');
    }

    /**
     * Relationship: BookingPayment belongs to a user
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship: BookingPayment belongs to a booking
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Relationship: BookingPayment belongs to a currency
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }
}
