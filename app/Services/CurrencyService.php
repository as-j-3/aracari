<?php

namespace App\Services;

use App\Models\Currency;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class CurrencyService
{
    private const CACHE_DURATION = 3600; // 1 ساعة
    
    public function baseCurrency(): ?Currency
    {
        $id = Setting::where('key', 'base_currency_id')->value('value');
        
        return Currency::find($id);
    }

    public function baseCurrencyId(): ?int
    {
        return (int) (Setting::where('key', 'base_currency_id')->value('value') ?? 1);
    }

    public function baseCurrencyCode(): string
    {
        return $this->baseCurrency()?->code ?? 'YER';
    }
    /**
     * تحويل المبلغ من عملة إلى عملة أخرى
     */
    public function convert(float $amount, string $fromCurrency, string $toCurrency): float
    {
        // إذا كانت العملتان متشابهتين
        if ($fromCurrency === $toCurrency) {
            return $amount;
        }
        
        // الحصول على أسعار الصرف مقابل الريال اليمني
        $fromRate = $this->getExchangeRate($fromCurrency);
        $toRate = $this->getExchangeRate($toCurrency);
        
        // تحويل المبلغ إلى الريال اليمني أولاً
        $amountInYer = $amount * $fromRate;
        
        // ثم تحويل من الريال اليمني إلى العملة المستهدفة
        $result = $amountInYer / $toRate;
        
        return round($result, 2);
    }
    
    /**
     * تحويل المبلغ من الريال اليمني إلى عملة أخرى
     */
    public function convertFromYer(float $yerAmount, string $targetSymbol): float
    {
        if ($targetSymbol === 'YER') {
            return $yerAmount;
        }
        
        // تعطيل Cache مؤقتاً لحل مشكلة serialization
        // $cacheKey = "currency_rate_{$targetSymbol}";
        // $currency = Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($targetSymbol) {
        //     ...
        // });
        
        // استخدام استعلام مباشر لحل المشكلة
        $currency = Currency::where('symbol', $targetSymbol)->first();
        
        if (!$currency) {
            throw new \RuntimeException("العملة غير موجودة: {$targetSymbol}");
        }
        
        if (!is_object($currency)) {
            throw new \RuntimeException("بيانات العملة غير صالحة: {$targetSymbol}");
        }
        
        // استخدام isset() بدلاً من property_exists() لأنها تعمل بشكل أفضل مع Eloquent
        if (!isset($currency->price_to_yr)) {
            throw new \RuntimeException("خصائص العملة غير مكتملة: {$targetSymbol}");
        }
        
        $priceToYr = $currency->price_to_yr;
        
        if (!is_numeric($priceToYr) || $priceToYr <= 0) {
            throw new \RuntimeException("سعر الصرف غير صالح للعملة: {$targetSymbol}");
        }
        
        return round($yerAmount / $priceToYr, 2);
    }
    
    /**
     * الحصول على سعر صرف عملة محددة
     */
    public function getExchangeRate(string $symbol): float
    {
        // تعطيل Cache مؤقتاً لحل مشكلة serialization
        // $cacheKey = "currency_rate_{$symbol}";
        // return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($symbol) {
        //     ...
        // });
        
        // استخدام استعلام مباشر لحل المشكلة
        $currency = Currency::where('symbol', $symbol)->first();
        
        if (!$currency) {
            return 1.0; // القيمة الافتراضية
        }
        
        if (!is_object($currency)) {
            return 1.0; // القيمة الافتراضية
        }
        
        $priceToYr = $currency->price_to_yr;
        
        if (!is_numeric($priceToYr) || $priceToYr <= 0) {
            return 1.0; // القيمة الافتراضية
        }
        
        return $priceToYr;
    }
    
    /**
     * الحصول على جميع العملات المتاحة
     */
    public function getAvailableCurrencies(): array
    {
        return Cache::remember('available_currencies', self::CACHE_DURATION, function () {
            return Currency::select('symbol', 'name', 'price_to_yr')
                ->orderBy('name')
                ->get()
                ->toArray();
        });
    }
    
    /**
     * تحويل المبلغ إلى عدة عملات
     */
    public function convertToMultipleCurrencies(float $yerAmount): array
    {
        $currencies = $this->getAvailableCurrencies();
        $result = ['YER' => $yerAmount];
        
        foreach ($currencies as $currency) {
            if ($currency['symbol'] !== 'YER') {
                $result[$currency['symbol']] = $this->convertFromYer($yerAmount, $currency['symbol']);
            }
        }
        
        return $result;
    }
public function getAllCurrenciesWithRates(): array
{
    $baseCurrency = $this->baseCurrency();
    if (!$baseCurrency) return [];
    
    return Cache::remember('currencies_with_rates', 300, function () use ($baseCurrency) {
        return Currency::orderBy('name')->get()->map(function ($currency) use ($baseCurrency) {
            // نفس العملة = 1
            if ($currency->id === $baseCurrency->id) {
                return [
                    'id' => $currency->id,
                    'code' => $currency->code,
                    'name' => $currency->name,
                    'rate_to_base' => 1.0,
                ];
            }
            
            // جيب آخر سعر من exchange_rates
            $rate = $this->getLatestExchangeRate($baseCurrency->id, $currency->id);
            
            return [
                'id' => $currency->id,
                'code' => $currency->code,
                'name' => $currency->name,
                'rate_to_base' => $rate,
            ];
        })->toArray();
    });
}

    public function getLatestExchangeRate(int $baseCurrencyId, int $targetCurrencyId): float
    {
        if ($baseCurrencyId === $targetCurrencyId) {
            return 1.0;
        }
        
        $direct = \App\Models\ExchangeRate::where('from_currency_id', $baseCurrencyId)
            ->where('to_currency_id', $targetCurrencyId)
            ->latest('rate_date')
            ->first();
        
        if ($direct) {
            return round((float) $direct->rate, 4);
        }
        
        $reverse = \App\Models\ExchangeRate::where('from_currency_id', $targetCurrencyId)
            ->where('to_currency_id', $baseCurrencyId)
            ->latest('rate_date')
            ->first();
        
        if ($reverse) {
            return round(1 / (float) $reverse->rate, 4);
        }
        
        $baseCurrency = Currency::find($baseCurrencyId);
        $targetCurrency = Currency::find($targetCurrencyId);
        
        if ($baseCurrency && $targetCurrency && $baseCurrency->price_to_yr > 0) {
            return round($targetCurrency->price_to_yr / $baseCurrency->price_to_yr, 4);
        }
        
        return 1.0;
    }
}
