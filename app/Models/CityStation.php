<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CityStation extends Model
{
    protected $fillable = [
        'uuid',
        'city_id',
        'name',
        'status',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }
    
    public function trips()
    {
        return $this->hasMany(Trip::class, 'pickup_location_id')
            ->orWhere('arrival_location_id', $this->id);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}