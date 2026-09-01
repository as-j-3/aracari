<?php

namespace App\Models;

use App\Models\UserStatus;
use App\Constants\UserStatus as UserStatusConstant;
use App\Constants\UserTypes as UserTypeConstant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Traits\HasPermissions;
use App\Traits\HasAudit;
use App\Traits\BranchAccessTrait;
use App\Services\ProviderContext;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasAudit, HasApiTokens, HasFactory, Notifiable, HasPermissions, BranchAccessTrait, SoftDeletes;

    /**
     * Attributes that are mass assignable
     */
    protected $fillable = [
        'name',
        'phone_number',
        'email',
        'password',
        'uuid',
        'phone_verified_at',
        'status_id',
        'type_user_code',
        'user_parent',
        'is_approved',
    ];

    /**
     * Attributes hidden from JSON serialization
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Attribute casting for type safety
     */
    protected $casts = [
        'is_approved' => 'boolean',
        'phone_verified_at' => 'datetime',
    ];

    /**
     * Boot the model
     * 
     * Handles:
     * - Automatic UUID generation for new users
     * - Cache clearing when permissions or branch access changes
     */
    protected static function boot(): void
    {
        parent::boot();
        
        static::creating(function ($user) {
            if (empty($user->uuid)) {
                $user->uuid = (string) \Illuminate\Support\Str::uuid();
            }
        });
        
        static::updated(function ($user) {            
            // Clear branch access cache when user type or parent changes
            if ($user->wasChanged('type_user_code') || $user->wasChanged('user_parent')) {
                $user->clearBranchAccessCache();
            }
        });
        
        static::deleted(function ($user) {
            $user->clearPermissionCache();
            $user->clearBranchAccessCache();
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
     * Relationship: User has a status
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function status()
    {
        return $this->belongsTo(UserStatus::class);
    }

    /**
     * Relationship: User belongs to a user type
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function userType()
    {
        return $this->belongsTo(TypeOfUser::class, 'type_user_code', 'id');
    }

    /**
     * Relationship: User may have a parent user (for hierarchical structure)
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parent()
    {
        return $this->belongsTo(User::class, 'user_parent');
    }

    /**
     * Relationship: User may have child users (for hierarchical structure)
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function children()
    {
        return $this->hasMany(User::class, 'user_parent');
    }

    /**
     * Relationship: User belongs to many branches (for provider staff)
     * 
     * This relationship connects users to provider branches through the
     * branch_users pivot table. Used for staff members assigned to branches.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function branches()
    {
        return $this->belongsToMany(
            ProviderBranch::class,
            'branch_users',
            'user_id',
            'branch_id'
        )->withTimestamps();
    }

    /**
     * Relationship: User has many verification records
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function verifications()
    {
        return $this->hasMany(UserVerification::class);
    }

    /**
     * Relationship: User has many roles
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles')->withPivot('granted_by');
    }

    /**
     * Relationship: User has many device tokens for push notifications
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function deviceTokens()
    {
        return $this->hasMany(DeviceToken::class);
    }

    /**
     * Relationship: User has many login activity records
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function loginActivities()
    {
        return $this->hasMany(LoginActivity::class);
    }

    /**
     * Relationship: User has many advertisements
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function ads()
    {
        return $this->hasMany(Ad::class);
    }

    /**
     * Relationship: User has many vehicles
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function vehicles()
    {
        return $this->hasMany(Vehicle::class);
    }

    /**
     * Relationship: User has many trip reservations through bookings
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function reservations()
    {
        return $this->hasManyThrough(TripReservation::class, Booking::class);
    }


    /**
     * Relationship: User has one provider service record
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function providerService()
    {
        return $this->hasOne(ProviderService::class);
    }

    /**
     * Check if user is a provider
     * 
     * @return bool True if user type is provider
     */
    public function isAdmin(): bool
    {
        return (int) $this->type_user_code === UserTypeConstant::ADMIN;
    }

    public function isProvider(): bool
    {
        return (int) $this->type_user_code === UserTypeConstant::PROVIDER;
    }

    public function isCustomer(): bool
    {
        return (int) $this->type_user_code === UserTypeConstant::CUSTOMER;
    }

    public function isProviderOwner(): bool
    {
        return $this->isProvider() && $this->user_parent === null;
    }

    public function isProviderOrStaff(): bool
    {
        return $this->isProvider();  
    }


    /**
     * Check if user has a specific role
     *
     * Business logic:
     * - Checks if user has the specified role
     *
     * @param string $roleName Role name to check
     * @return bool True if user has the role
     */
    public function hasRole(string $roleName): bool
    {
        return $this->roles()->where('name', $roleName)->exists();
    }

    /**
     * Check if user is provider staff (not owner)
     * 
     * Business logic:
     * - Checks if user is provider type and has a parent
     * 
     * @return bool True if user is provider staff
     */
    public function isProviderStaff(): bool
    {
        return $this->isProvider() && $this->user_parent !== null;
    }

    /**
     * Relationship: User has many created trips
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function trips()
    {
        return $this->hasMany(Trip::class, 'created_by_user', 'id');
    }

    /**
     * Relationship: User has many bookings
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Relationship: User belongs to provider owner
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function providerOwner()
    {
        return $this->belongsTo(User::class, 'user_parent');
    }

    /**
     * Relationship: User has many created coupons
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function coupons()
    {
        return $this->hasMany(Coupon::class);
    }

    /**
     * Relationship: User has many coupon usage records
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function couponUsages()
    {
        return $this->hasMany(CouponUsage::class);
    }

    /**
     * Resolve provider service ID for this user
     * 
     * Business logic:
     * - Uses ProviderContext to determine provider service
     * - Returns null if not a provider
     * 
     * @return int|null Provider service ID
     */
    public function resolveProviderServiceId(): ?int
    {
        return app(ProviderContext::class)->providerServiceIdFor($this);
    }

    /**
     * Resolve provider user ID for this user
     * 
     * Business logic:
     * - Uses ProviderContext to determine provider user
     * - Returns null if not a provider
     * 
     * @return int|null Provider user ID
     */
    public function resolveProviderUserId(): ?int
    {
        return app(ProviderContext::class)->providerUserIdFor($this);
    }

    /**
     * Check if user status is active
     * 
     * Business logic:
     * - Compares user status ID with active status
     * 
     * @return bool True if user is active
     */
    public function isActive(): bool
    {
        $activeStatus = \App\Models\UserStatus::where('status_code', 'active')->first();
        return $activeStatus && $this->status_id === $activeStatus->id;
    }

    /**
     * Check if user status is pending
     * 
     * Business logic:
     * - Compares user status ID with pending status
     * 
     * @return bool True if user is pending
     */
    public function isPending(): bool
    {
        $pendingStatus = \App\Models\UserStatus::where('status_code', 'pending')->first();
        return $pendingStatus && $this->status_id === $pendingStatus->id;
    }

    /**
     * Check if user status is suspended
     * 
     * Business logic:
     * - Checks if status ID matches suspended constant
     * 
     * @return bool True if user is suspended
     */
    public function isSuspended(): bool
    {
        return $this->status_id === UserStatusConstant::SUSPENDED;
    }

    /**
     * Check if user is an agent
     * 
     * Business logic:
     * - Uses UserType constant to check agent type
     * 
     * @return bool True if user is an agent
     */
    public function isAgent(): bool
    {
        return UserTypeConstant::isAgent($this->type_user_code);
    }

    /**
     * Check if user is a driver
     * 
     * Business logic:
     * - Uses UserType constant to check driver type
     * 
     * @return bool True if user is a driver
     */
    public function isDriver(): bool
    {
        return UserTypeConstant::isDriver($this->type_user_code);
    }

    /**
     * Accessor: Get user type name
     * 
     * Business logic:
     * - Uses UserType constant to get name
     * 
     * @return string User type name
     */
    public function getUserTypeNameAttribute(): string
    {
        return UserTypeConstant::getName($this->type_user_code);
    }

    /**
     * Accessor: Get status code string
     * 
     * @return string|null Status code
     */
    public function getStatusCodeAttribute(): ?string
    {
        return $this->status?->status_code;
    }

    /**
     * Accessor: Get user type code string
     * 
     * @return string|null User type code
     */
    public function getUserTypeCodeStringAttribute(): ?string
    {
        return $this->userType?->type_user_code;
    }

    /**
     * Accessor: Get profile photo URL
     * 
     * Business logic:
     * - Returns placeholder avatar URL
     * 
     * @return string Profile photo URL
     */
    public function getProfilePhotoUrlAttribute(): string
    {
        return asset('images/avatar-placeholder.svg');
    }
    
}
