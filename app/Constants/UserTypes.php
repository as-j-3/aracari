<?php

namespace App\Constants;


class UserTypes
{

    public const CUSTOMER = 1;
    
    public const PROVIDER = 2;
    
    public const ADMIN = 3;

    public const AGENT = 4;

    public const DRIVER = 5;

 
    public static function getAll(): array
    {
        return [
            self::CUSTOMER => 'العميل',
            self::PROVIDER => 'مزود خدمة',
            self::ADMIN => 'المدير',
            self::AGENT => 'الوكيل',
            self::DRIVER => 'السائق',
        ];
    }
    
    public static function getName(int $code): string
    {
        return self::getAll()[$code] ?? 'غير معروف';
    }
    
    public static function isCustomer(int $code): bool
    {
        return $code === self::CUSTOMER;
    }
    
    public static function isProvider(int $code): bool
    {
        return $code === self::PROVIDER;
    }
    

    public static function isAdmin(int $code): bool
    {
        return $code === self::ADMIN;
    }
    
    public static function isAgent(int $code): bool
    {
        return $code === self::AGENT;
    }
    

    public static function isDriver(int $code): bool
    {
        return $code === self::DRIVER;
    }
    

    public static function getAdminAccessTypes(): array
    {
        return [
            self::ADMIN,
            self::PROVIDER,
            self::AGENT,
        ];
    }
    

    public static function getTripCreatorTypes(): array
    {
        return [
            self::PROVIDER,
            self::ADMIN,
        ];
    }
    

    public static function getApprovalRequiredTypes(): array
    {
        return [
            self::PROVIDER,
            self::AGENT,
        ];
    }
    

    public static function getValidationRule(): string
    {
        return 'in:' . implode(',', array_keys(self::getAll()));
    }
}
