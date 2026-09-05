<?php

namespace App\Constants;

use App\Models\Account;

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

     public static function providerAccount(): string
    {
        $lastCode = Account::where('account_code', 'like', '2200%')
            ->whereRaw('LENGTH(account_code) = 6')
            ->orderByDesc('account_code')
            ->value('account_code');

        if (! $lastCode) {
            return '220001';
        }

        return (string) ((int) $lastCode + 1);
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