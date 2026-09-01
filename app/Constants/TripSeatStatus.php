<?php

namespace App\Constants;

/**
 * ثوابت حالات مقاعد الرحلات المسجلة في جدول trip_seat_statuses
 */
class TripSeatStatus
{
    public const AVAILABLE = 1;
    public const HELD      = 2;
    public const BOOKED    = 3;
    public const BLOCKED   = 4;
}
