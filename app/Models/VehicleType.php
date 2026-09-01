<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class VehicleType extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'type_name',
    ];

    protected $casts = [
        'uuid' => 'string',
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

    /**
     * Get the route key name for URL generation.
     * All admin routes use UUID-based routing.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Get the vehicles for the vehicle type.
     */
    public function vehicles()
    {
        return $this->hasMany(Vehicle::class);
    }
}
