<?php

namespace App\Models;

use App\Models\PaymentMethod as PaymentMethodModel;
use App\Traits\BelongsToProvider;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'reject_reason',
        'verified_at',
        'verified_by_user',
        'paid_status'
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
     * @return BelongsTo
     */
    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethodModel::class, 'payment_method_id');
    }

    /**
     * Relationship: BookingPayment belongs to a user
     *
     * @return BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship: BookingPayment belongs to a booking
     *
     * @return BelongsTo
     */
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Relationship: BookingPayment belongs to a currency
     *
     * @return BelongsTo
     */
    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function userDirectPaymentInfo()
    {
        return $this->hasOne(
            UserDirectPaymentInfo::class,
            'booking_payment_id'
        );
    }
}
