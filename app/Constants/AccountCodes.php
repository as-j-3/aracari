<?php

namespace App\Constants;

final class AccountCodes
{
    private function __construct() {}

    const ASSETS      = '1000'; 
    const LIABILITIES = '2000'; 
    const EXPENSES    = '3000'; 
    const REVENUE     = '4000'; 

    const CASH               = '1001'; 
    const BANK               = '1002';
    const WALLET_LIABILITY   = '2001'; 
    const PROVIDER_REVENUE   = '4001'; 
    const PLATFORM_COMMISSION = '4002';

    public static function providerAccount(int $userId): string
    {
        return self::LIABILITIES . '_' . $userId;
    }
    public static function getAccountType(string $code): ?string
    {
        return match(substr($code, 0, 1)) {
            '1' => AccountTypes::ASSET,
            '2' => AccountTypes::LIABILITY,
            '3' => AccountTypes::EXPENSE,
            '4' => AccountTypes::REVENUE,
            default => null
        };
    }
}