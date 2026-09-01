<?php

namespace App\Models;

use App\Traits\BelongsToProvider;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TripReservation extends Model
{
    use BelongsToProvider, HasFactory;

    protected string $providerOwnershipRelation = 'trip.user.providerService';

    protected $table = 'trip_reservations';

    protected $fillable = [
        'uuid',
        'booking_id',
        'trip_id',
        'direction',
        'status',
        'cancelled_at',
    ];

    protected $casts = [
        'uuid' => 'string',
        'cancelled_at' => 'datetime',
    ];

    /**
     * Get the route key name for URL generation.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    // Accessors لحساب السعر المحذوف
    public function getPriceAtBookingAttribute(): float
    {
        // 1. محاولة الحصول من التذاكر
        $ticketPrice = $this->tickets()->sum('price_at_booking');
        
        if ($ticketPrice > 0) {
            return $ticketPrice;
        }
        
        // 2. حساب من الرحلة + الضرائب
        $tripPrice = $this->trip->price_for_adult * $this->getAdultsCount() 
                   + $this->trip->price_for_child * $this->getChildrenCount();
        $taxes = $this->booking->bookingTaxes()->sum('tax_amount');
        
        return $tripPrice + $taxes;
    }
    
    private function getAdultsCount(): int
    {
        return $this->tickets()
            ->whereHas('passenger', function($query) {
                $query->where('age', '>=', 18);
            })
            ->count();
    }
    
    private function getChildrenCount(): int
    {
        return $this->tickets()
            ->whereHas('passenger', function($query) {
                $query->where('age', '<', 18);
            })
            ->count();
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

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function trip()
    {
        return $this->belongsTo(Trip::class, 'trip_id', 'id');
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }
}
