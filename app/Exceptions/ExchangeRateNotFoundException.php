<?php

namespace App\Exceptions;

use RuntimeException;

class ExchangeRateNotFoundException extends RuntimeException
{
    public static function forPair(int $fromId, int $toId, ?string $date = null): self
    {
        $dateSuffix = $date ? " on {$date}" : '';
        return new self(
            "No exchange rate found from currency [{$fromId}] to currency [{$toId}]{$dateSuffix}."
        );
    }
}