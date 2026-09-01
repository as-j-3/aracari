<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Type of User model
 * 
 * Represents different user types in the system (customer, provider, admin)
 */
class TypeOfUser extends Model
{
    use HasFactory;

    /**
     * User type constants
     */
    const CUSTOMER_CODE = 'customer';
    const PROVIDER_CODE = 'provider';
    const ADMIN_CODE = 'admin';

    /**
     * Default user type IDs (fallback values)
     */
    const DEFAULT_CUSTOMER_ID = 1;
    const DEFAULT_PROVIDER_ID = 2;
    const DEFAULT_ADMIN_ID = 3;

    protected $fillable = [
        'type_name',
        'type_user_code',
    ];

    /**
     * Get the users for the type of user.
     */
    public function users()
    {
        return $this->hasMany(User::class, 'type_user_code', 'id');
    }

    /**
     * Find user type by code
     *
     * @param string $code The user type code
     * @return self|null The user type instance or null
     */
    public static function findByCode(string $code): ?self
    {
        return static::where('type_user_code', $code)->first();
    }

    /**
     * Get customer user type with fallback
     *
     * @return self The customer user type
     */
    public static function getCustomerType(): self
    {
        return static::findByCode(self::CUSTOMER_CODE) 
            ?? static::find(self::DEFAULT_CUSTOMER_ID)
            ?? new self(['type_user_code' => self::CUSTOMER_CODE]);
    }

    /**
     * Get provider user type with fallback
     *
     * @return self The provider user type
     */
    public static function getProviderType(): self
    {
        return static::findByCode(self::PROVIDER_CODE) 
            ?? static::find(self::DEFAULT_PROVIDER_ID)
            ?? new self(['type_user_code' => self::PROVIDER_CODE]);
    }
}
