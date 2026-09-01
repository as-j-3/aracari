<?php

namespace App\Traits;

trait NormalizesPhone
{
    protected function normalizePhone(?string $countryCode, ?string $phone): ?string
    {
        return ($countryCode && $phone) 
            ? '+' . $countryCode . ltrim($phone, '0') 
            : null;
    }
}