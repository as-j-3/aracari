<?php

namespace App\Constants;

class FileStatus
{
    public const ACTIVE   = 'active';
    public const INACTIVE = 'inactive';

    public static function all(): array
    {
        return [
            self::ACTIVE   => 'نشط',
            self::INACTIVE => 'غير نشط',
        ];
    }

    public static function toggle(string $status): string
    {
        return $status === self::ACTIVE ? self::INACTIVE : self::ACTIVE;
    }
}