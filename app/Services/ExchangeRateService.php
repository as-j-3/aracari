<?php

namespace App\Services;

use App\Models\ExchangeRate;
use App\Models\Currency;
use App\Models\Setting;
use App\Exceptions\ExchangeRateNotFoundException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExchangeRateService
{
    private const CACHE_TTL = 3600;

    /**
     * الحصول على سعر الصرف بين عملتين (الأحدث أو بتاريخ محدد)
     *
     * @throws ExchangeRateNotFoundException
     */
    public function getRate(int $fromCurrencyId, int $toCurrencyId, ?string $date = null): float
    {
        if ($fromCurrencyId === $toCurrencyId) {
            return 1.0;
        }

        $date ??= today()->toDateString();
        $cacheKey = "exchange_rate:{$fromCurrencyId}:{$toCurrencyId}:{$date}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($fromCurrencyId, $toCurrencyId, $date) {
            $rate = ExchangeRate::where('from_currency_id', $fromCurrencyId)
                ->where('to_currency_id', $toCurrencyId)
                ->where('rate_date', '<=', $date)
                ->latest('rate_date')
                ->value('rate');

            if ($rate !== null) {
                return (float) $rate;
            }

            $reverseRate = ExchangeRate::where('from_currency_id', $toCurrencyId)
                ->where('to_currency_id', $fromCurrencyId)
                ->where('rate_date', '<=', $date)
                ->latest('rate_date')
                ->value('rate');

            if ($reverseRate !== null && $reverseRate > 0) {
                return 1 / $reverseRate;
            }

            try {
                return $this->calculateViaBase($fromCurrencyId, $toCurrencyId, $date);
            } catch (ExchangeRateNotFoundException $e) {
                throw ExchangeRateNotFoundException::forPair($fromCurrencyId, $toCurrencyId, $date);
            }
        });
    }

    /**
     * تحويل مبلغ بين عملتين
     *
     * @throws ExchangeRateNotFoundException
     */
    public function convert(float $amount, int $fromCurrencyId, int $toCurrencyId, ?string $date = null): float
    {
        if ($fromCurrencyId === $toCurrencyId) {
            return $amount;
        }

        $rate = $this->getRate($fromCurrencyId, $toCurrencyId, $date);
        $precision = $this->getCurrencyPrecision($toCurrencyId);

        return round($amount * $rate, $precision);
    }

    /**
     * حساب سعر الصرف عبر العملة الأساسية
     *
     * @throws ExchangeRateNotFoundException
     */
    private function calculateViaBase(int $fromCurrencyId, int $toCurrencyId, ?string $date = null): float
    {
        $baseCurrencyId = $this->getBaseCurrencyId();

        if ($fromCurrencyId === $baseCurrencyId) {
            return $this->getDirectRateOrThrow($baseCurrencyId, $toCurrencyId, $date);
        }
        if ($toCurrencyId === $baseCurrencyId) {
            return $this->getDirectRateOrThrow($fromCurrencyId, $baseCurrencyId, $date);
        }

        $fromToBase = $this->getDirectRateOrThrow($fromCurrencyId, $baseCurrencyId, $date);
        $baseToTo = $this->getDirectRateOrThrow($baseCurrencyId, $toCurrencyId, $date);

        return round($fromToBase * $baseToTo, 6);
    }

    /**
     * جلب السعر المباشر أو رفع exception
     *
     * @throws ExchangeRateNotFoundException
     */
    private function getDirectRateOrThrow(int $fromId, int $toId, ?string $date = null): float
    {
        $rate = ExchangeRate::where('from_currency_id', $fromId)
            ->where('to_currency_id', $toId)
            ->where('rate_date', '<=', $date ?? today()->toDateString())
            ->latest('rate_date')
            ->value('rate');

        if ($rate === null) {
            $reverse = ExchangeRate::where('from_currency_id', $toId)
                ->where('to_currency_id', $fromId)
                ->where('rate_date', '<=', $date ?? today()->toDateString())
                ->latest('rate_date')
                ->value('rate');

            if ($reverse !== null && $reverse > 0) {
                return 1 / $reverse;
            }

            throw ExchangeRateNotFoundException::forPair($fromId, $toId, $date);
        }

        return (float) $rate;
    }

    /**
     * إضافة سعر صرف جديد
     *
     * @throws \InvalidArgumentException
     */
    public function addRate(
        int $fromCurrencyId,
        int $toCurrencyId,
        float $rate,
        ?string $rateDate = null,
        ?int $auditedBy = null
    ): ExchangeRate {
        if ($fromCurrencyId === $toCurrencyId) {
            throw new \InvalidArgumentException('من العملة وإلى العملة يجب أن يكونا مختلفين.');
        }
        if ($rate <= 0) {
            throw new \InvalidArgumentException('سعر الصرف يجب أن يكون أكبر من صفر.');
        }

        $rateDate ??= today()->toDateString();

        try {
            DB::beginTransaction();

            $exchangeRate = ExchangeRate::create([
                'from_currency_id' => $fromCurrencyId,
                'to_currency_id'   => $toCurrencyId,
                'rate'             => $rate,
                'rate_date'        => $rateDate,
            ]);

            DB::afterCommit(function () use ($fromCurrencyId, $toCurrencyId, $rateDate) {
                $this->clearRateCache($fromCurrencyId, $toCurrencyId, $rateDate);
            });

            DB::commit();

            return $exchangeRate;
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            if ($e->errorInfo[1] === 1062) {
                throw new \InvalidArgumentException('يوجد سعر صرف مسجل بالفعل لهذا الزوج في هذا التاريخ.');
            }
            throw $e;
        }
    }

    /**
     * جلب العملة الأساسية مع cache
     */
    private function getBaseCurrencyId(): int
    {
        return (int) cache()->remember('base_currency_id', self::CACHE_TTL, function () {
            return (int) (Setting::where('key', 'base_currency_id')->value('value') ?? 1);
        });
    }

    /**
     * جلب دقة العملة (عدد المنازل العشرية)
     */
    private function getCurrencyPrecision(int $currencyId): int
    {
        return cache()->remember("currency_precision:{$currencyId}", self::CACHE_TTL, function () use ($currencyId) {
            return Currency::where('id', $currencyId)->value('decimal_places') ?? 2;
        });
    }

    /**
     * مسح cache لزوج عملات (الاتجاهين)
     */
    public function clearRateCache(int $fromId, int $toId, ?string $date = null): void
    {
        $date ??= today()->toDateString();
        cache()->forget("exchange_rate:{$fromId}:{$toId}:{$date}");
        cache()->forget("exchange_rate:{$toId}:{$fromId}:{$date}");
    }

    /**
     * مسح كل cache لأسعار الصرف
     */
    public function flushAllRateCache(): void
    {
        if (method_exists(cache()->getStore(), 'tags')) {
            cache()->tags(['exchange_rates'])->flush();
        } else {
            cache()->increment('exchange_rates_version');
        }
    }
}