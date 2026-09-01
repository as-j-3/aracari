<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Models\Currency;
use App\Services\TaxRuleService;
use App\Services\CurrencyService;
use App\Services\ExchangeRateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TripTaxController extends Controller
{
    public function __construct(
        private TaxRuleService $taxRuleService,
        private CurrencyService $currencyService,
        private ExchangeRateService $exchangeRateService
    ) {}

    public function calculate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'trip_uuids' => 'required|array|min:1|max:20',  //  max:20 لمنع DoS
            'trip_uuids.*' => 'required|exists:trips,uuid',
            'payment_method_id' => 'nullable|exists:payment_methods,id',
            'currency_id' => 'nullable|exists:currencies,id',
        ], [
            'trip_uuids.required' => 'يرجى تقديم معرفات الرحلات.',
            'trip_uuids.array' => 'يجب أن تكون معرفات الرحلات في شكل مصفوفة.',
            'trip_uuids.min' => 'يجب تقديم معرف رحلة واحدة على الأقل.',
            'trip_uuids.*.exists' => 'الرحلة المختارة غير متوفرة',
            'payment_method_id.exists' => 'طريقة الدفع المختارة غير متوفرة',
            'currency_id.exists' => 'العملة المختارة غير متوفرة',
        ]);

        $trips = Trip::whereIn('uuid', $validated['trip_uuids'])
            ->with(['departureCity', 'user.providerService', 'service'])
            ->when(!auth()->user()?->isAdmin(), function ($query) {
                $query->where('is_public', true);
            })
            ->get();

        if ($trips->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'الرحلات غير موجودة',
            ], 404);
        }

        $baseCurrencyId = $this->currencyService->baseCurrencyId();
        $paymentCurrencyId = $validated['currency_id'] ?? $baseCurrencyId;
        $exchangeRate = $this->exchangeRateService->getRate($paymentCurrencyId, $baseCurrencyId);
        $currency = Currency::find($paymentCurrencyId);

        $taxData = $this->taxRuleService->getBulkApplicableTaxes($trips, $validated['payment_method_id'] ?? null);

        $allTaxes = [];
        $totalTax = 0;
        $totalBaseTax = 0;

        foreach ($taxData['taxes'] as $tax) {
            $baseAmount = $tax['amount'];
            $convertedAmount = round($baseAmount * $exchangeRate, 2);

            $allTaxes[] = [
                'tax_id'           => $tax['tax_id'],
                'tax_name'         => $tax['name'] ?? $tax['tax_name'],
                'tax_type'         => $tax['type'] ?? $tax['tax_type'],
                'tax_rate'         => $tax['rate'] ?? $tax['tax_rate'],
                'tax_amount'       => $convertedAmount,
                'currency'         => $currency->code ?? 'USD',
                'exchange_rate'    => $exchangeRate,
                'base_tax_amount'  => $baseAmount,
            ];

            $totalTax += $convertedAmount;
            $totalBaseTax += $baseAmount;
        }

        return response()->json([
            'success'          => true,
            'taxes'            => $allTaxes,
            'taxes_total'      => $totalTax,
            'taxes_total_base' => $totalBaseTax,
            'currency'         => $currency->code ?? 'USD',
            'exchange_rate'    => $exchangeRate,
        ]);
    }
}