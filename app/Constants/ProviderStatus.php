<?php

namespace App\Constants;

class ProviderStatus
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

    public static function getLabel(string $status): ?string
    {
        return self::all()[$status] ?? null;
    }

    public static function toggle(string $status): string
    {
        return $status === self::ACTIVE ? self::INACTIVE : self::ACTIVE;
    }

    public static function isValid(string $status): bool
    {
        return array_key_exists($status, self::all());
    }
}