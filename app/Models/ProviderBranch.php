<?php

namespace App\Models;

use App\Traits\BelongsToProvider;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ProviderBranch extends Model
{
    use BelongsToProvider, HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'provider_id',
        'phone_number',
        'country_id',
        'city_id',
        'address',
        'latitude',
        'longitude',
        'status',
    ];

    /**
     * Boot function to auto-generate UUID
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

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'status' => 'string',
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

    /**
     * Get the country for the provider branch.
     */
    public function country()
    {
        return $this->belongsTo(Country::class);
    }
    /**
     * Get all trips for this branch (through Service).
     * Used by route model binding for nested routes.
     */
    public function trips()
    {
        return $this->hasManyThrough(
            Trip::class,
            Service::class,
            'branch_id',  
            'service_id',  
            'id',          
            'id'           
        );
    }
    /**
     * Get the city for the provider branch.
     */
    public function city()
    {
        return $this->belongsTo(City::class);
    }

    /**
     * Get the provider service for the provider branch.
     */
    public function provider()
    {
        return $this->belongsTo(ProviderService::class, 'provider_id');
    }

    /**
     * Alias for provider relationship
     */
    public function providerService()
    {
        return $this->belongsTo(ProviderService::class, 'provider_id');
    }

    /**
     * Alias for provider relationship
     */
    public function service()
    {
        return $this->belongsTo(ProviderService::class, 'provider_id');
    }

    /**
     * Get the branch users for the provider branch.
     */
    public function branchUsers()
    {
        return $this->hasMany(BranchUser::class, 'branch_id');
    }
    
    public function bookings()
    {
        return $this->hasMany(Booking::class, 'branch_id');
    }
}
