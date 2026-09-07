<?php

namespace Database\Seeders;

use App\Models\Tax;
use App\Models\Country;
use App\Models\CountryTax;
use App\Models\ProviderTax;
use App\Models\CommissionTax;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TaxSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();

        if (!$user) {
            throw new \Exception('لا يوجد مستخدم في جدول users.');
        }

        /*
        |--------------------------------------------------------------------------
        | الدول
        |--------------------------------------------------------------------------
        */

        $yemen = Country::where('iso_code', 'YE')->firstOrFail();
        $saudi = Country::where('iso_code', 'SA')->firstOrFail();
        $uae   = Country::where('iso_code', 'AE')->firstOrFail();
        $egypt = Country::where('iso_code', 'EG')->firstOrFail();

        /*
        |--------------------------------------------------------------------------
        | 1) تعريفات الضرائب
        |--------------------------------------------------------------------------
        */

        // ضريبة قيمة التذكرة
        $ticketTax = Tax::updateOrCreate(
            [
                'name' => 'ضريبة القيمة المضافة',
            ],
            [
                'uuid'       => (string) Str::uuid(),
                'created_by' => $user->id,
            ]
        );

        // ضريبة عمولة أراكاري
        $commissionTax = Tax::updateOrCreate(
            [
                'name' => 'ضريبة الجماهير',
            ],
            [
                'uuid'       => (string) Str::uuid(),
                'created_by' => $user->id,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 2) ضرائب التذكرة حسب الدولة
        |--------------------------------------------------------------------------
        */

        $ticketSaudi = CountryTax::updateOrCreate(
            [
                'tax_id'     => $ticketTax->id,
                'country_id' => $saudi->id,
            ],
            [
                'type'      => 'percentage',
                'rate'      => 15.00,
                'amount'    => null,
                'is_active' => true,
            ]
        );

        $ticketEgypt = CountryTax::updateOrCreate(
            [
                'tax_id'     => $ticketTax->id,
                'country_id' => $egypt->id,
            ],
            [
                'type'      => 'percentage',
                'rate'      => 14.00,
                'amount'    => null,
                'is_active' => true,
            ]
        );

        $ticketUae = CountryTax::updateOrCreate(
            [
                'tax_id'     => $ticketTax->id,
                'country_id' => $uae->id,
            ],
            [
                'type'      => 'percentage',
                'rate'       => 5.00,
                'amount'     => null,
                'is_active' => true,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 3) ضريبة عمولة أراكاري حسب الدولة
        |--------------------------------------------------------------------------
        */

        $commissionSaudi = CountryTax::updateOrCreate(
            [
                'tax_id'     => $commissionTax->id,
                'country_id' => $saudi->id,
            ],
            [
                'type'      => 'percentage',
                'rate'      => 15.00,
                'amount'    => null,
                'is_active' => true,
            ]
        );

        $commissionEgypt = CountryTax::updateOrCreate(
            [
                'tax_id'     => $commissionTax->id,
                'country_id' => $egypt->id,
            ],
            [
                'type'      => 'percentage',
                'rate'      => 14.00,
                'amount'    => null,
                'is_active' => true,
            ]
        );

        $commissionUae = CountryTax::updateOrCreate(
            [
                'tax_id'     => $commissionTax->id,
                'country_id' => $uae->id,
            ],
            [
                'type'      => 'percentage',
                'rate'      => 5.00,
                'amount'    => null,
                'is_active' => true,
            ]
        );

        $commissionYemen = CountryTax::updateOrCreate(
            [
                'tax_id'     => $commissionTax->id,
                'country_id' => $yemen->id,
            ],
            [
                'type'      => 'percentage',
                'rate'      => 3.00,
                'amount'    => null,
                'is_active' => true,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 4) ربط ضرائب التذكرة في provider_taxes
        |--------------------------------------------------------------------------
        |
        | service_id = NULL
        | تعني أن الضريبة عامة وليست مرتبطة بخدمة محددة.
        |
        */

        ProviderTax::updateOrCreate(
            [
                'country_tax_id' => $ticketSaudi->id,
                'service_id'     => null,
            ],
            [
                'is_active' => true,
            ]
        );

        ProviderTax::updateOrCreate(
            [
                'country_tax_id' => $ticketEgypt->id,
                'service_id'     => null,
            ],
            [
                'is_active' => true,
            ]
        );

        ProviderTax::updateOrCreate(
            [
                'country_tax_id' => $ticketUae->id,
                'service_id'     => null,
            ],
            [
                'is_active' => true,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 5) ربط ضرائب العمولة في commission_taxes
        |--------------------------------------------------------------------------
        |
        | service_id = NULL
        | لأن ضريبة عمولة أراكاري تعتمد على الدولة.
        |
        */

        CommissionTax::updateOrCreate(
            [
                'country_tax_id' => $commissionSaudi->id,
                'service_id'     => null,
            ],
            [
                'is_active' => true,
            ]
        );

        CommissionTax::updateOrCreate(
            [
                'country_tax_id' => $commissionEgypt->id,
                'service_id'     => null,
            ],
            [
                'is_active' => true,
            ]
        );

        CommissionTax::updateOrCreate(
            [
                'country_tax_id' => $commissionUae->id,
                'service_id'     => null,
            ],
            [
                'is_active' => true,
            ]
        );

        CommissionTax::updateOrCreate(
            [
                'country_tax_id' => $commissionYemen->id,
                'service_id'     => null,
            ],
            [
                'is_active' => true,
            ]
        );
    }
}