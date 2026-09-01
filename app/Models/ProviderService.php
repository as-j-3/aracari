<?php

namespace App\Models;

use App\Traits\BelongsToProvider;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ProviderService extends Model
{
    use BelongsToProvider, HasFactory;

    protected string $providerOwnershipColumn = 'user_id';
    protected string $providerOwnershipScope = 'provider_user';

    protected $fillable = [
        'user_id',
        'type_of_service_id',
        'name',
        'description',
        'cr_number',
        'brand_photo',
        'born_country',
        'contact_phone',
        'contact_email',
        'website',
        'status',
        'commission_type',
        'uuid',
    ];

    protected $casts = [
        'uuid' => 'string',
        'status' => 'string',
        'commission_type' => 'string',
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
     * Get the user for the provider service.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the type of service for the provider service.
     */
    public function typeOfService()
    {
        return $this->belongsTo(TypeOfService::class);
    }

    /**
     * Get the provider branches for the provider service.
     */
    public function providerBranches()
    {
        return $this->hasMany(ProviderBranch::class, 'provider_id');
    }

    /** Get the tax rules for the provider service.
     */
    public function taxRules()
    {
        return $this->hasMany(TaxRule::class, 'provider_id');
    }
    /**
     * Alias for providerBranches (used by older queries).
     */
    public function branches()
    {
        return $this->providerBranches();
    }

    /**
     * Get the provider files for the provider service.
     */
    public function providerFiles()
    {
        return $this->hasMany(ProviderFile::class, 'provider_id');
    }

    /**
     * Alias for providerFiles (used by older queries).
     */
    public function files()
    {
        return $this->providerFiles();
    }

    /**
     * جلب رابط الشعار
     */
    public function getBrandPhotoUrlAttribute(): ?string
    {
        //return $this->brand_photo ? (new \App\Services\FileUploadService())->getFileUrl($this->brand_photo) : null;
        if (!$this->brand_photo) return null;
    
        return asset('storage/' . $this->brand_photo);

    }

    /**
     * Get the receive revenue methods for the provider service.
     */
    public function receiveRevenueMethods()
    {
        return $this->hasMany(ReceiveRevenueMethod::class, 'provider_id');
    }

    /**
     * Get the service commissions for the provider service.
     */
    public function serviceCommissions()
    {
        return $this->hasMany(ServiceCommission::class, 'provider_id');
    }
}
