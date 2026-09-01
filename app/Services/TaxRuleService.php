<?php

namespace App\Services;

use App\Models\TaxRule;
use App\Models\Trip;
use Illuminate\Support\Collection;

class TaxRuleService
{
    public function __construct(
        private CurrencyService $currencyService
    ) {}

    public function getApplicableTaxes(Trip $trip, float $taxableAmount, ?int $paymentMethodId = null): array
    {
        $rules = $this->getActiveRules($trip, $paymentMethodId);

        $taxDetails = [];

        foreach ($rules as $rule) {
            if (!$rule->tax) {
                continue;
            }

            if (!$this->isWithinAmountRange($rule, $taxableAmount)) {
                continue;
            }

            $taxAmount = $this->calculateTaxAmount($taxableAmount, $rule->tax);

            if ($taxAmount > 0) {
                $taxDetails[] = [
                    'tax_id'  => $rule->tax->id,
                    'name'    => $rule->tax->name,
                    'type'    => $rule->tax->type,
                    'rate'    => $rule->tax->rate,
                    'amount'  => $taxAmount,
                ];
            }
        }

        return $taxDetails;
    }


    public function getTotalTax(Trip $trip, float $taxableAmount, ?int $paymentMethodId = null): float
    {
        $taxes = $this->getApplicableTaxes($trip, $taxableAmount, $paymentMethodId);

        return array_sum(array_column($taxes, 'amount'));
    }


    /**
     * جلب القواعد الضريبية المطبقة على رحلة
     *
     * المنطق:
     * - كل قاعدة لها شروط (country, provider, service_type, trip, payment_method)
     * - القاعدة تطبق إذا كانت كل الشروط المعبأة فيها تطابق الرحلة
     * - الشروط الفارغة (NULL) تعني "أي قيمة"
     *
     * @param Trip $trip الرحلة
     * @param int|null $paymentMethodId طريقة الدفع
     * @return Collection قواعد الضرائب المطبقة
     */
    private function getActiveRules(Trip $trip, ?int $paymentMethodId = null): Collection
    {
        $countryId = $trip->departureCity?->country_id;
        $providerId = $trip->user?->providerService?->id;
        $serviceTypeId = $trip->service?->type_of_service_id;
        $tripId = $trip->id;

        return TaxRule::where('is_active', true)
            ->where(function ($query) use ($countryId, $providerId, $serviceTypeId, $paymentMethodId, $tripId) {
                //  كل شرط يطبق AND على الشروط المعبأة فيه
                // هذا يمنع تكرار الضريبة عبر مسارات مطابقة متعددة

                $query->where(function ($q) use ($countryId) {
                    $q->whereNull('country_id')->orWhere('country_id', $countryId);
                })
                ->where(function ($q) use ($providerId) {
                    $q->whereNull('provider_id')->orWhere('provider_id', $providerId);
                })
                ->where(function ($q) use ($serviceTypeId) {
                    $q->whereNull('service_type_id')->orWhere('service_type_id', $serviceTypeId);
                })
                ->where(function ($q) use ($tripId) {
                    $q->whereNull('trip_id')->orWhere('trip_id', $tripId);
                })
                ->where(function ($q) use ($paymentMethodId) {
                    $q->whereNull('payment_method_id')->orWhere('payment_method_id', $paymentMethodId);
                });
            })
            ->with('tax')
            ->get();
    }

    private function isWithinAmountRange(TaxRule $rule, float $amount): bool
    {
        if ($rule->min_amount !== null && $amount < $rule->min_amount) {
            return false;
        }

        if ($rule->max_amount !== null && $amount > $rule->max_amount) {
            return false;
        }

        return true;
    }


    private function calculateTaxAmount(float $amount, $tax, ?int $ruleCurrencyId = null): float
    {
        if ($tax->type === 'percentage' && $tax->rate) {
            return round($amount * ((float) $tax->rate / 100), 2);
        }

        if ($tax->type === 'fixed' && $tax->amount) {
            if ($ruleCurrencyId && $tax->currency_id) {
                // الضريبة ثابتة بعملة معينة - نسيبها كما هي
                return (float) $tax->amount;
            }
            
            return (float) $tax->amount;
        }

        return 0;
    }
        public function getBulkApplicableTaxes(Collection $trips, ?int $paymentMethodId = null): array
    {
        $countryIds = $trips->pluck('departureCity.country_id')->filter()->unique();
        $providerIds = $trips->pluck('user.providerService.id')->filter()->unique();
        $serviceTypeIds = $trips->pluck('service.type_of_service_id')->filter()->unique();
        $tripIds = $trips->pluck('id');

        //  تحميل كل القواعد النشطة مرة واحدة، ثم فلترتها في PHP
        // هذا يمنع تكرار الضريبة عبر orWhere غير محصور
        $rules = TaxRule::where('is_active', true)->with('tax')->get();

        $allTaxes = [];

        foreach ($trips as $trip) {
            //  المبلغ الخاضع للضريبة = سعر البالغ + الطفل (كان يأخذ سعر البالغ فقط)
            $taxableAmount = (float) ($trip->price_for_adult ?? 0) + (float) ($trip->price_for_child ?? 0);

            //  فلترة بـ AND (كل شرط إما NULL أو يطابق)
            $tripRules = $rules->filter(function ($rule) use ($trip, $paymentMethodId) {
                $countryId = $trip->departureCity?->country_id;
                $providerId = $trip->user?->providerService?->id;
                $serviceTypeId = $trip->service?->type_of_service_id;

                $matchesCountry = is_null($rule->country_id) || $rule->country_id === $countryId;
                $matchesProvider = is_null($rule->provider_id) || $rule->provider_id === $providerId;
                $matchesServiceType = is_null($rule->service_type_id) || $rule->service_type_id === $serviceTypeId;
                $matchesTrip = is_null($rule->trip_id) || $rule->trip_id === $trip->id;
                $matchesPaymentMethod = is_null($rule->payment_method_id) || $rule->payment_method_id === $paymentMethodId;

                //  كل الشروط يجب أن تطابق (AND) وليس (OR)
                return $matchesCountry && $matchesProvider && $matchesServiceType && $matchesTrip && $matchesPaymentMethod;
            });

            foreach ($tripRules as $rule) {
                if (!$rule->tax || !$this->isWithinAmountRange($rule, $taxableAmount)) {
                    continue;
                }

                $taxAmount = $this->calculateTaxAmount($taxableAmount, $rule->tax);

                if ($taxAmount > 0) {
                    $allTaxes[] = [
                        'tax_id'  => $rule->tax->id,
                        'name'    => $rule->tax->name,
                        'type'    => $rule->tax->type,
                        'rate'    => $rule->tax->rate,
                        'amount'  => $taxAmount,
                    ];
                }
            }
        }

        return ['taxes' => $allTaxes];
    }
}