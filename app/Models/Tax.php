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
        'type',
        'rate',
        'amount',
        'currency_id',
        'created_by',
    ];

    protected $casts = [
        'uuid' => 'string',
        'rate' => 'decimal:2',
        'amount' => 'decimal:2',
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
}
