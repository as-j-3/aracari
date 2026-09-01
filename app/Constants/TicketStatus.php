<?php

namespace App\Constants;

class TicketStatus
{
    public const ACTIVE = 'active';
    public const CANCELLED = 'cancelled';
    public const BOARDED = 'boarded'; 

    public static function getAll(): array
    {
        return [
            self::ACTIVE,
            self::CANCELLED,
            self::BOARDED,
        ];
    }
}