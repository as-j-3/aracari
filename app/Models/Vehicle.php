<?php

namespace App\Models;

use App\Traits\BelongsToProvider;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Vehicle extends Model
{
    use BelongsToProvider, HasFactory;

    protected string $providerOwnershipColumn = 'user_id';
    protected string $providerOwnershipScope = 'provider_user';

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    protected $fillable = [
        'uuid',
        'name',
        'user_id',
        'vehicle_type_id',
        'model',
        'outside_photo',
        'inside_photo',
        'seats_count',
        'status',
    ];

    protected $casts = [
        'uuid' => 'string',
    ];

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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function vehicleType()
    {
        return $this->belongsTo(VehicleType::class, 'vehicle_type_id');
    }

    public function trips()
    {
        return $this->hasMany(Trip::class, 'vehicle_id', 'id');
    }

    public function seats()
    {
        return $this->hasMany(Seat::class, 'vehicle_id');
    }

    /**
     * جلب رابط الصورة الخارجية
     */
    public function getOutsidePhotoUrlAttribute(): ?string
    {
        return $this->outside_photo ? asset('storage/' . $this->outside_photo) : null;
    }

    /**
     * جلب رابط الصورة الداخلية
     */
    public function getInsidePhotoUrlAttribute(): ?string
    {
        return $this->inside_photo ? asset('storage/' . $this->inside_photo) : null;
    }
}
