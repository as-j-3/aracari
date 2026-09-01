<?php

namespace App\Constants;

class WeekDays
{
    const SATURDAY = 1;
    const SUNDAY = 2;
    const MONDAY = 3;
    const TUESDAY = 4;
    const WEDNESDAY = 5;
    const THURSDAY = 6;
    const FRIDAY = 7;

    public static function all(): array
    {
        return [
            self::SATURDAY => 'السبت',
            self::SUNDAY => 'الأحد',
            self::MONDAY => 'الإثنين',
            self::TUESDAY => 'الثلاثاء',
            self::WEDNESDAY => 'الأربعاء',
            self::THURSDAY => 'الخميس',
            self::FRIDAY => 'الجمعة',
        ];
    }

    public static function databaseValues(): array
    {
        return [
            'saturday',
            'sunday',
            'monday',
            'tuesday',
            'wednesday',
            'thursday',
            'friday',
        ];
    }

    public static function toDatabaseValue(int|string|null $day): ?string
    {
        if ($day === null) {
            return null;
        }

        if (is_string($day) && in_array($day, self::databaseValues(), true)) {
            return $day;
        }

        $map = [
            self::SATURDAY => 'saturday',
            self::SUNDAY => 'sunday',
            self::MONDAY => 'monday',
            self::TUESDAY => 'tuesday',
            self::WEDNESDAY => 'wednesday',
            self::THURSDAY => 'thursday',
            self::FRIDAY => 'friday',
        ];

        return $map[(int) $day] ?? null;
    }

    public static function fromCarbonDay(int $carbonDay): ?int
    {
        $map = [
            0 => self::SUNDAY,   
            1 => self::MONDAY,   
            2 => self::TUESDAY,  
            3 => self::WEDNESDAY,
            4 => self::THURSDAY,  
            5 => self::FRIDAY,   
            6 => self::SATURDAY, 
        ];
        return $map[$carbonDay] ?? null;
    }
}
