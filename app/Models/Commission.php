<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Commission extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'currency_id',
        'commission_type',
        'calculation_level',
        'description',
    ];

    protected $casts = [
        'uuid' => 'string',
        'commission_type' => 'string',
        'calculation_level' => 'string',
    ];

    /**
     * Model boot method for automatic UUID generation
     *
     * This method automatically generates a UUID for new commissions
     * if not provided during creation.
     */
    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) \Illuminate\Support\Str::uuid();
            }
        });
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

    public function serviceCommissions()
    {
        return $this->hasMany(ServiceCommission::class);
    }

    public function bookingCommissions()
    {
        return $this->hasMany(BookingCommission::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }
}
