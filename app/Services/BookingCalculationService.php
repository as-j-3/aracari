<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Tax;
use App\Models\BookingTax;
use App\Models\Setting;
use App\Constants\CouponStatus as CouponStatusConst;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Booking Calculation Service
 *
 * مسؤول عن كل الحسابات المالية للحجوزات:
 * - حساب المبلغ الأساسي (البالغين + الأطفال)
 * - تطبيق خصم الكوبونات (مع منع double-spend)
 * - حساب الضرائب المطبقة
 *
 * الأمان:
 * - التحقق من الكوبون داخل transaction مع lockForUpdate
 * - لا يتم تطبيق ضرائب غير نشطة
 * - لا يتم تخزين ضرائب كل الرحلات في cache واحد
 */
class BookingCalculationService
{
    private const TAXES_CACHE_TTL_MINUTES = 60;

    public function __construct(
        private CurrencyService $currencyService,
        private TaxRuleService $taxRuleService
    ) {}

    /**
     * حساب تكاليف الحجز الكاملة
     *
     * @param float $baseBookingAmount المبلغ الأساسي قبل الخصم
     * @param Coupon|null $appliedCoupon الكوبون المطبق
     * @param array $associatedTrips الرحلات المرتبطة
     * @param float $discountAmount مبلغ الخصم
     * @return array تفاصيل الحساب
     */
    public function calculateBookingCosts(
        float $baseBookingAmount,
        ?Coupon $appliedCoupon,
        array $associatedTrips,
        float $discountAmount = 0.0
    ): array {
        $taxableAmount = max(0, $baseBookingAmount - $discountAmount);

        $taxDetails = [];
        $taxTotal = 0.0;

        // حساب الضرائب لكل رحلة على حدة
        if (!empty($associatedTrips)) {
            foreach ($associatedTrips as $trip) {
                //  استخدم سعر الرحلة مضروباً في عدد الركاب (كان يضيف سعر البالغ + الطفل بدون ضرب)
                $tripTaxableAmount = $this->calculateTripTaxableAmount($trip, $taxableAmount, $baseBookingAmount);
                $tripTaxes = $this->taxRuleService->getApplicableTaxes($trip, $tripTaxableAmount);

                foreach ($tripTaxes as $tax) {
                    $taxDetails[] = $tax;
                    $taxTotal += $tax['amount'];
                }
            }
        }

        // fallback: لو ما في رحلات، احسب ضرائب عامة
        if (empty($taxDetails)) {
            $taxCalculationResult = $this->calculateTaxes($taxableAmount);
            $taxTotal = $taxCalculationResult['total_tax'];
            $taxDetails = $taxCalculationResult['details'];
        }

        $finalTotalAmount = $taxableAmount + $taxTotal;

        return [
            'base_amount' => round($baseBookingAmount, 2),
            'discount_amount' => round($discountAmount, 2),
            'taxable_amount' => round($taxableAmount, 2),
            'tax_total' => round($taxTotal, 2),
            'total_amount' => round($finalTotalAmount, 2),
            'coupon' => $appliedCoupon,
            'tax_details' => $taxDetails,
        ];
    }

    /**
     * حساب المبلغ الخاضع للضريبة لرحلة معينة
     */
    private function calculateTripTaxableAmount($trip, float $totalTaxableAmount, float $totalBaseAmount): float
    {
        if ($totalBaseAmount <= 0) {
            return 0;
        }

        // نسبة الرحلة من إجمالي المبلغ الأساسي
        $tripBaseAmount = ($trip->price_for_adult ?? 0) + ($trip->price_for_child ?? 0);
        $tripRatio = $tripBaseAmount / $totalBaseAmount;

        return $totalTaxableAmount * $tripRatio;
    }

    /**
     * حساب المبلغ الإجمالي (legacy compatibility)
     *
     * @param array $trips الرحلات
     * @param int $adultsCount عدد البالغين
     * @param int $childrenCount عدد الأطفال
     * @param string|null $couponCode كود الكوبون
     * @param int|null $userId معرف المستخدم
     * @return array نتيجة الحساب
     */
    public function calculateTotalAmount(
        array $trips,
        int $adultsCount,
        int $childrenCount,
        ?string $couponCode = null,
        ?int $userId = null
    ): array {
        $baseAmount = $this->calculateBaseAmount($trips, $adultsCount, $childrenCount);

        $discountAmount = 0.0;
        $appliedCoupon = null;

        if ($couponCode && $userId) {
            //  استخدم CouponLockService لمنع double-spend
            $couponResult = $this->validateAndApplyCoupon($baseAmount, $couponCode, $userId);
            $discountAmount = $couponResult['discount_amount'];
            $appliedCoupon = $couponResult['coupon'];
        }

        $taxableAmount = max(0, $baseAmount - $discountAmount);

        return [
            'baseAmount' => round($baseAmount, 2),
            'discountAmount' => round($discountAmount, 2),
            'taxableAmount' => round($taxableAmount, 2),
            'coupon' => $appliedCoupon,
            'total_amount' => round($taxableAmount, 2),  //  أضف total_amount للتوافق
            'tax_details' => [],  //  أضف tax_details للتوافق
        ];
    }

    /**
     * حساب المبلغ الأساسي من الرحلات وعدد الركاب
     */
    private function calculateBaseAmount(array $trips, int $adultsCount, int $childrenCount): float
    {
        $totalAmount = 0.0;

        foreach ($trips as $trip) {
            $adultPrice = (float) ($trip->price_for_adult ?? 0);
            $childPrice = (float) ($trip->price_for_child ?? 0);
            $totalAmount += ($adultsCount * $adultPrice) + ($childrenCount * $childPrice);
        }

        return $totalAmount;
    }

    /**
     *  التحقق من الكوبون وتطبيقه داخل transaction آمن
     *
     * @param float $baseAmount المبلغ الأساسي
     * @param string $couponCode كود الكوبون
     * @param int $userId معرف المستخدم
     * @return array ['discount_amount' => float, 'coupon' => ?Coupon]
     */
    private function validateAndApplyCoupon(float $baseAmount, string $couponCode, int $userId): array
    {
        return DB::transaction(function () use ($baseAmount, $couponCode, $userId) {
            // قفل صف الكوبون لمنع double-spend
            $coupon = Coupon::where('code', $couponCode)
                ->where('status_id', CouponStatusConst::ACTIVE)
                ->lockForUpdate()
                ->first();

            if (!$coupon) {
                return ['discount_amount' => 0.0, 'coupon' => null];
            }

            // التحقق من فترة الصلاحية
            if ($coupon->start_date && now()->lt($coupon->start_date)) {
                return ['discount_amount' => 0.0, 'coupon' => null];
            }
            if ($coupon->end_date && now()->gt($coupon->end_date)) {
                return ['discount_amount' => 0.0, 'coupon' => null];
            }

            // التحقق من الحد الأقصى الكلي للاستخدام
            if ($coupon->usage_limit) {
                $totalUsage = $coupon->usages()->lockForUpdate()->count();
                if ($totalUsage >= $coupon->usage_limit) {
                    return ['discount_amount' => 0.0, 'coupon' => null];
                }
            }

            // التحقق من الحد الأقصى للاستخدام لكل مستخدم
            if ($userId && $coupon->user_limit) {
                $userUsage = $coupon->usages()
                    ->where('user_id', $userId)
                    ->lockForUpdate()
                    ->count();
                if ($userUsage >= $coupon->user_limit) {
                    return ['discount_amount' => 0.0, 'coupon' => null];
                }
            }

            // حساب مبلغ الخصم
            $discountAmount = $this->calculateCouponDiscount($coupon, $baseAmount);

            return [
                'discount_amount' => min($discountAmount, $baseAmount),
                'coupon' => $coupon,
            ];
        });
    }

    /**
     * حساب مبلغ خصم الكوبون
     */
    private function calculateCouponDiscount(Coupon $coupon, float $baseAmount): float
    {
        if ($coupon->type === 'fixed') {
            return (float) $coupon->amount;
        }

        // percentage
        $discount = $baseAmount * ((float) $coupon->percentage / 100);

        // تطبيق الحد الأقصى للخصم لو موجود
        if ($coupon->max_discount) {
            $discount = min($discount, (float) $coupon->max_discount);
        }

        return $discount;
    }

    /**
     * حساب الضرائب المطبقة
     *
     * @param float $taxableBaseAmount المبلغ الخاضع للضريبة
     * @return array ['total_tax' => float, 'details' => array]
     */
    private function calculateTaxes(float $taxableBaseAmount): array
    {
        $applicableTaxes = Cache::remember(
            'all_applicable_taxes',
            now()->addMinutes(self::TAXES_CACHE_TTL_MINUTES),
            function () {
                return Tax::all();
            }
        );

        $totalTaxAmount = 0.0;
        $taxBreakdownDetails = [];

        foreach ($applicableTaxes as $individualTax) {
            $currentTaxAmount = $this->calculateSingleTaxAmount($individualTax, $taxableBaseAmount);

            $totalTaxAmount += $currentTaxAmount;
            $taxBreakdownDetails[] = [
                'tax_id' => $individualTax->id,
                'tax_name' => $individualTax->name,
                'tax_type' => $individualTax->type,
                'tax_rate' => $individualTax->rate,
                'tax_amount' => round($currentTaxAmount, 2),
            ];
        }

        return [
            'total_tax' => round($totalTaxAmount, 2),
            'details' => $taxBreakdownDetails,
        ];
    }

    /**
     * حساب مبلغ ضريبة واحدة
     */
    private function calculateSingleTaxAmount(Tax $tax, float $taxableAmount): float
    {
        if ($tax->type === 'fixed') {
            return (float) $tax->amount;
        }

        // percentage
        $rate = (float) $tax->rate;
        if ($rate <= 0 || $rate > 100) {
            Log::warning('Invalid tax rate detected', [
                'tax_id' => $tax->id,
                'rate' => $rate,
            ]);
            return 0.0;
        }

        return $taxableAmount * ($rate / 100);
    }

    /**
     *  إنشاء سجلات ضرائب الحجز داخل transaction
     *
     * @param int $bookingId معرف الحجز
     * @param array $taxBreakdownDetails تفاصيل الضرائب
     * @param int|null $currencyId معرف العملة
     * @param float|null $exchangeRate سعر الصرف
     * @return void
     */
    public function createBookingTaxes(
        int $bookingId,
        array $taxBreakdownDetails,
        ?int $currencyId = null,
        ?float $exchangeRate = null
    ): void {
        $currencyId = $currencyId ?? (int) (Setting::where('key', 'base_currency_id')->value('value') ?? 1);
        $exchangeRate = $exchangeRate ?: 1.0;

        $taxRecords = [];
        foreach ($taxBreakdownDetails as $taxDetail) {
            $taxAmount = round(($taxDetail['amount'] ?? $taxDetail['tax_amount']) * $exchangeRate, 2);

            $taxRecords[] = [
                'booking_id' => $bookingId,
                'tax_id' => $taxDetail['tax_id'],
                'tax_name' => $taxDetail['name'] ?? $taxDetail['tax_name'],
                'tax_type_rate_or_amount' => $taxDetail['type'] ?? $taxDetail['tax_type'],
                'tax_rate' => $taxDetail['rate'] ?? $taxDetail['tax_rate'],
                'tax_amount' => $taxAmount,
                'currency_id' => $currencyId,
                'exchange_rate' => $exchangeRate,
                'base_tax_amount' => $exchangeRate > 0 ? round($taxAmount / $exchangeRate, 2) : $taxAmount,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        //  Bulk insert بدل loop منفصل (atomic)
        if (!empty($taxRecords)) {
            BookingTax::insert($taxRecords);
        }
    }
}
