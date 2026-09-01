<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TripWaypoint extends Model
{
    protected $fillable = [
        'uuid',
        'trip_id',
        'city_id',
        'city_station_id',
    ];

    /**
     * Get the route key name for URL generation.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * توليد الـ UUID تلقائياً عند إنشاء سجل جديد
     */
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
     * علاقة: نقطة المرور تنتمي لرحلة
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

   
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function station(): BelongsTo
    {
        return $this->belongsTo(CityStation::class, 'city_station_id');
    }
}