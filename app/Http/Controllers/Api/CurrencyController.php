<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CurrencyService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CurrencyController extends Controller
{
    use ApiResponse;
    
    public function __construct(private CurrencyService $currencyService) {}

    public function convert(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|string|exists:currencies,code',
        ]);

        try {
            $baseCurrency = $this->currencyService->baseCurrency();
            $targetCurrency = \App\Models\Currency::where('code', $request->currency)->first();
            
            $rate = $this->currencyService->getLatestExchangeRate($baseCurrency->id, $targetCurrency->id);
            $convertedAmount = round($request->amount * $rate, 2);

            return $this->success([
                'original_amount' => $request->amount,
                'original_currency' => $baseCurrency->code,
                'converted_amount' => $convertedAmount,
                'target_currency' => $request->currency,
                'rate' => $rate,
            ], "تم تحويل العملة بنجاح.", 200);

        } catch (\Exception $e) {
            Log::error('Currency convert failed: ' . $e->getMessage()); 
            return $this->error('فشل تحويل العملة. يرجى المحاولة مرة أخرى.', 400);
        }
    }

    public function currencies()
    {
        $currencies = $this->currencyService->getAllCurrenciesWithRates();
        $baseCurrency = $this->currencyService->baseCurrency();

        return $this->success([
            'base_currency' => [
                'id' => $baseCurrency->id,
                'code' => $baseCurrency->code,
                'name' => $baseCurrency->name,
            ],
            'currencies' => $currencies,
        ], "تم جلب قائمة العملات بنجاح.", 200);
    }

    public function convertToAll(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
        ]);

        try {
            $currencies = $this->currencyService->getAllCurrenciesWithRates();
            $baseCurrency = $this->currencyService->baseCurrency();
            $conversions = [];

            foreach ($currencies as $currency) {
                $conversions[$currency['code']] = round($request->amount * $currency['rate_to_base'], 2);
            }

            return $this->success([
                'amount' => $request->amount,
                'base_currency' => $baseCurrency->code,
                'conversions' => $conversions,
            ], "تم تحويل العملة إلى جميع العملات المتاحة.", 200);

        } catch (\Exception $e) {
            Log::error('Currency convertToAll failed: ' . $e->getMessage()); 
            return $this->error('فشل تحويل العملة. يرجى المحاولة مرة أخرى.', 400);
        }
    }
}