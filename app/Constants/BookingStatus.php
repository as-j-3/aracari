<?php

namespace App\Constants;

class BookingStatus
{
    const CONFIRMED = 1;
    const PENDING = 2;
    const CANCELLED = 3;
    const COMPLETED = 4;
    const CANCELLED_BY_USER = 5;
    const CANCELLED_BY_ADMIN = 6;
    const PAYMENT_FAILED = 7;
    const RESERVATION_ACTIVE = 'active';
    const RESERVATION_CANCELLED = 'cancelled';
    
    
    public static function getActiveStatuses(): array
    {
        return [self::CONFIRMED, self::PENDING];
    }
    
    public static function isActive($status): bool
    {
        return in_array($status, [self::CONFIRMED, self::PENDING, self::BOARDING_CONFIRMED], true);
    }

    public static function getCancelledStatuses(): array
    {
        return [self::CANCELLED, self::CANCELLED_BY_USER, self::CANCELLED_BY_ADMIN];
    }
    
    public static function isCancelled($status): bool
    {
        return in_array($status, self::getCancelledStatuses(), true);
    }
}