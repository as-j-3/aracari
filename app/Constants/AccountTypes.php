<?php

namespace App\Constants;

class AccountTypes
{
    public const ASSET = 'asset';
    public const LIABILITY = 'liability';
    public const REVENUE = 'revenue';
    public const EXPENSE = 'expense';

    /**
     * Return all valid account types as an array.
     *
     * @return string[]
     */
    public static function all(): array
    {
        return [
            self::ASSET,
            self::LIABILITY,
            self::REVENUE,
            self::EXPENSE,
        ];
    }
}