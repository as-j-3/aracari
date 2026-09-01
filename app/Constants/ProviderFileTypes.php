<?php

namespace App\Constants;

class ProviderFileTypes
{
    const COMMERCIAL_REGISTER = 'commercial_register';
    const MUNICIPALITY_LICENSE = 'municipality_license';
    const FOUNDATION_CONTRACT = 'foundation_contract';
    const TAX_CERTIFICATE = 'tax_certificate';
    const OTHER = 'other';

    public static function all(): array
    {
        return [
            self::COMMERCIAL_REGISTER => 'السجل التجاري',
            self::MUNICIPALITY_LICENSE => 'رخصة البلدية',
            self::FOUNDATION_CONTRACT => 'عقد التأسيس',
            self::TAX_CERTIFICATE => 'شهادة ضريبية',
            self::OTHER => 'أخرى',
        ];
    }

    public static function getLabel(string $key): string
    {
        return self::all()[$key] ?? $key;
    }
}