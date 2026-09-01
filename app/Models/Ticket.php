<?php

namespace App\Models;

use App\Traits\BelongsToProvider;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;


class Ticket extends Model
{
    use BelongsToProvider, HasFactory;

    protected string $providerOwnershipRelation = 'tripReservation.trip.user.providerService';

    protected $table = 'tickets';

    protected $fillable = [
        'uuid',
        'trip_reservation_id',
        'passenger_id',
        'seat_id',
        'ticket_number',
        'price_at_booking',
        'status',
        'boarded_at',
        'boarded_by',
        'cancellation_fee',
        'refund_amount',
        'cancelled_at',
    ];

    protected $casts = [
        'uuid' => 'string',
        'cancelled_at' => 'datetime',
        'boarded_at' => 'datetime',
    ];

    protected $appends = ['qr_validation_url'];

    /**
     * Get the route key name for URL generation.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * رابط التحقق من صحة التذكرة - يُستخدم لتوليد QR في الواجهة
     */
    public function getQrValidationUrlAttribute(): string
    {
        return url("/api/tickets/validate/{$this->uuid}");
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

    public function tripReservation()
    {
        return $this->belongsTo(TripReservation::class);
    }

    public function passenger()
    {
        return $this->belongsTo(Passenger::class);
    }

    public function seat()
    {
        return $this->belongsTo(Seat::class, 'seat_id');
    }
    
    public function boardedBy()
    {
        return $this->belongsTo(User::class, 'boarded_by');
    }
}
