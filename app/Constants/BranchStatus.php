<?php

namespace App\Constants;

class BranchStatus
{
    public const ACTIVE   = 'active';
    public const INACTIVE = 'inactive';
    public const PENDING  = 'pending';

    public static function all(): array
    {
        return [
            self::ACTIVE   => 'نشط',
            self::INACTIVE => 'غير نشط',
            self::PENDING  => 'قيد الانتظار',
        ];
    }

    public static function toggle(string $status): string
    {
        return $status === self::ACTIVE ? self::INACTIVE : self::ACTIVE;
    }
}