<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Tax extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'name',
        "is_visible_for_customer",
    ];

    protected $casts = [
        'uuid' => 'string',
        'is_visible_for_customer' => 'boolean',
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

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function bookingTaxes()
    {
        return $this->hasMany(BookingTax::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }
    public function scopeVisibleForCustomer($query)
    {
        return $query->where('is_visible_for_customer', true);
    }
    public function countryTaxes()
    {
        return $this->hasMany(CountryTax::class, 'tax_id');
    }
}
