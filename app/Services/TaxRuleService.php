<?php

namespace App\Services;

use App\Models\ProviderTax;
use Illuminate\Support\Facades\Log;

class TaxRuleService
{
    /**
     * جلب ضرائب المزود المرتبطة بالخدمة.
     *
     * يعتمد فقط على:
     * - service_id
     * - country_tax_id
     * - is_active
     */
    public function getApplicableProviderTaxes(
        $trip,
        float $ticketAmount
    ): array {
        if ($ticketAmount <= 0) {
            return [];
        }

        /*
        |--------------------------------------------------------------------------
        | 1. تحديد الخدمة
        |--------------------------------------------------------------------------
        */

        $serviceId = $trip->service_id ?? $trip->service?->id;

        if (! $serviceId) {
            Log::warning('Service not found for provider tax calculation', [
                'trip_id' => $trip->id ?? null,
            ]);

            return [];
        }

        /*
        |--------------------------------------------------------------------------
        | 2. جلب جميع ضرائب المزود المرتبطة بالخدمة
        |--------------------------------------------------------------------------
        */

        $providerTaxes = ProviderTax::query()
            ->where('service_id', $serviceId)
            ->where('is_active', true)
            ->whereHas('countryTax', function ($query) {
                $query->where('is_active', true);
            })
            ->with([
                'countryTax.tax',
                'countryTax.country',
            ])
            ->get();

        /*
        |--------------------------------------------------------------------------
        | 3. حساب الضرائب
        |--------------------------------------------------------------------------
        */

        $taxDetails = [];

        foreach ($providerTaxes as $providerTax) {

            $countryTax = $providerTax->countryTax;
            $tax = $countryTax?->tax;

            if (! $countryTax || ! $tax) {
                continue;
            }

            $taxAmount = 0.0;
            $rate = null;

            /*
            |--------------------------------------------------------------------------
            | Percentage
            |--------------------------------------------------------------------------
            */

            if ($countryTax->type === 'percentage') {

                $rate = (float) $countryTax->rate;

                if ($rate <= 0 || $rate > 100) {
                    Log::warning('Invalid provider tax rate', [
                        'provider_tax_id' => $providerTax->id,
                        'country_tax_id' => $countryTax->id,
                        'rate' => $rate,
                    ]);

                    continue;
                }

                $taxAmount = round(
                    $ticketAmount * ($rate / 100),
                    2
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Fixed
            |--------------------------------------------------------------------------
            */

            elseif ($countryTax->type === 'fixed') {

                $taxAmount = round(
                    (float) ($countryTax->amount ?? 0),
                    2
                );
            }

            if ($taxAmount <= 0) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | 4. إضافة الضريبة للنتيجة
            |--------------------------------------------------------------------------
            */

            $taxDetails[] = [
                'tax_id' => $tax->id,
                'tax_name' => $tax->name,

                'type' => $countryTax->type,

                'rate' => $rate,

                'amount' => $taxAmount,

                'base_amount' => round(
                    $ticketAmount,
                    2
                ),

                'country_id' => $countryTax->country_id,

                'service_id' => $providerTax->service_id,

                'provider_tax_id' => $providerTax->id,

                'country_tax_id' => $countryTax->id,
            ];
        }

        return $taxDetails;
    }

    /**
     * حساب إجمالي ضرائب المزود.
     */
    public function getTotalProviderTax(
        $trip,
        float $ticketAmount
    ): float {
        $taxes = $this->getApplicableProviderTaxes(
            $trip,
            $ticketAmount
        );

        return round(
            array_sum(
                array_column($taxes, 'amount')
            ),
            2
        );
    }
}
