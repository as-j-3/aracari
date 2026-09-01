<?php

namespace App\Constants;

class UserRoles
{
    public const ADMIN = 'admin';
    public const PROVIDER = 'provider';
    public const CUSTOMER = 'customer';
    public const FINANCE_MANAGER = 'finance_manager';

    public static function systemRoles(): array
    {
        return [
            self::ADMIN,
            self::PROVIDER,
            self::CUSTOMER,
            self::FINANCE_MANAGER,
        ];
    }
}