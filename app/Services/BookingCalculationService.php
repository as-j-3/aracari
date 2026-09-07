<?php

namespace App\Services;

use App\Constants\CouponStatus as CouponStatusConst;
use App\Models\BookingTax;
use App\Models\Coupon;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

class BookingCalculationService
{
    public function __construct(
        private CurrencyService $currencyService,
        private TaxRuleService $taxRuleService
    ) {}

    /**
     * حساب تكاليف الحجز:
     * - السعر الأساسي
     * - الخصم
     * - ضريبة المزود على التذكرة فقط
     */
    public function calculateBookingCosts(
        float $baseBookingAmount,
        ?Coupon $appliedCoupon,
        array $associatedTrips,
        float $discountAmount = 0.0
    ): array {
        /*
        |--------------------------------------------------------------------------
        | 1. المبلغ الخاضع للضريبة
        |--------------------------------------------------------------------------
        */

        $taxableAmount = max(
            0,
            $baseBookingAmount - $discountAmount
        );

        $taxDetails = [];
        $taxTotal = 0.0;

        /*
        |--------------------------------------------------------------------------
        | 2. حساب ضريبة المزود لكل رحلة
        |--------------------------------------------------------------------------
        */

        if (! empty($associatedTrips)) {

            foreach ($associatedTrips as $trip) {

                /*
                |--------------------------------------------------------------------------
                | حساب المبلغ الخاضع للضريبة الخاص بالرحلة
                |--------------------------------------------------------------------------
                */

                $tripTaxableAmount = $this->calculateTripTaxableAmount(
                    $trip,
                    $taxableAmount,
                    $baseBookingAmount
                );

                if ($tripTaxableAmount <= 0) {
                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | جلب ضرائب المزود المرتبطة بالخدمة
                |--------------------------------------------------------------------------
                */

                $providerTaxes = $this->taxRuleService
                    ->getApplicableProviderTaxes(
                        $trip,
                        $tripTaxableAmount
                    );

                /*
                |--------------------------------------------------------------------------
                | إضافة جميع ضرائب المزود
                |--------------------------------------------------------------------------
                */

                foreach ($providerTaxes as $providerTax) {

                    $taxDetails[] = $providerTax;

                    $taxTotal += (float) $providerTax['amount'];
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 3. الإجمالي النهائي
        |--------------------------------------------------------------------------
        */

        $taxTotal = round($taxTotal, 2);

        $finalTotalAmount = round(
            $taxableAmount + $taxTotal,
            2
        );

        /*
        |--------------------------------------------------------------------------
        | 4. النتيجة
        |--------------------------------------------------------------------------
        */

        return [
            'base_amount' => round(
                $baseBookingAmount,
                2
            ),

            'discount_amount' => round(
                $discountAmount,
                2
            ),

            'taxable_amount' => round(
                $taxableAmount,
                2
            ),

            'tax_total' => $taxTotal,

            'total_amount' => $finalTotalAmount,

            'coupon' => $appliedCoupon,

            'tax_details' => $taxDetails,
        ];
    }

    /**
     * حساب المبلغ الأساسي للحجز.
     */
    private function calculateBaseAmount(
        array $trips,
        int $adultsCount,
        int $childrenCount
    ): float {
        $totalAmount = 0.0;

        foreach ($trips as $trip) {

            $adultPrice = (float) (
                $trip->price_for_adult ?? 0
            );

            $childPrice = (float) (
                $trip->price_for_child ?? 0
            );

            $totalAmount +=
                ($adultsCount * $adultPrice)
                +
                ($childrenCount * $childPrice);
        }

        return round($totalAmount, 2);
    }

    /**
     * حساب المبلغ الخاضع للضريبة الخاص بالرحلة.
     *
     * يتم توزيع المبلغ الخاضع للضريبة على الرحلات
     * حسب نسبة قيمة كل رحلة من إجمالي الحجز.
     */
    private function calculateTripTaxableAmount(
        $trip,
        float $totalTaxableAmount,
        float $totalBaseAmount
    ): float {
        if ($totalBaseAmount <= 0) {
            return 0.0;
        }

        /*
        |--------------------------------------------------------------------------
        | قيمة الرحلة الأساسية
        |--------------------------------------------------------------------------
        */

        $tripBaseAmount =
            (float) ($trip->price_for_adult ?? 0)
            +
            (float) ($trip->price_for_child ?? 0);

        if ($tripBaseAmount <= 0) {
            return 0.0;
        }

        /*
        |--------------------------------------------------------------------------
        | نسبة الرحلة من إجمالي الحجز
        |--------------------------------------------------------------------------
        */

        $tripRatio =
            $tripBaseAmount / $totalBaseAmount;

        return round(
            $totalTaxableAmount * $tripRatio,
            2
        );
    }

    /**
     * حساب الإجمالي - للحفاظ على التوافق مع الكود القديم.
     */
    public function calculateTotalAmount(
        array $trips,
        int $adultsCount,
        int $childrenCount,
        ?string $couponCode = null,
        ?int $userId = null
    ): array {
        /*
        |--------------------------------------------------------------------------
        | 1. حساب المبلغ الأساسي
        |--------------------------------------------------------------------------
        */

        $baseAmount = $this->calculateBaseAmount(
            $trips,
            $adultsCount,
            $childrenCount
        );

        /*
        |--------------------------------------------------------------------------
        | 2. حساب الخصم
        |--------------------------------------------------------------------------
        */

        $discountAmount = 0.0;
        $appliedCoupon = null;

        if ($couponCode && $userId) {

            $couponResult = $this->validateAndApplyCoupon(
                $baseAmount,
                $couponCode,
                $userId
            );

            $discountAmount =
                $couponResult['discount_amount'];

            $appliedCoupon =
                $couponResult['coupon'];
        }

        /*
        |--------------------------------------------------------------------------
        | 3. المبلغ الخاضع للضريبة
        |--------------------------------------------------------------------------
        */

        $taxableAmount = max(
            0,
            $baseAmount - $discountAmount
        );

        /*
        |--------------------------------------------------------------------------
        | النتيجة
        |--------------------------------------------------------------------------
        |
        | هذه الدالة قديمة للتوافق.
        | الحساب الرئيسي للضرائب يتم عبر calculateBookingCosts().
        |
        */

        return [
            'baseAmount' => round(
                $baseAmount,
                2
            ),

            'discountAmount' => round(
                $discountAmount,
                2
            ),

            'taxableAmount' => round(
                $taxableAmount,
                2
            ),

            'coupon' => $appliedCoupon,

            'total_amount' => round(
                $taxableAmount,
                2
            ),

            'tax_details' => [],
        ];
    }

    /**
     * التحقق من الكوبون وتطبيقه.
     */
    private function validateAndApplyCoupon(
        float $baseAmount,
        string $couponCode,
        int $userId
    ): array {
        return DB::transaction(
            function () use (
                $baseAmount,
                $couponCode,
                $userId
            ) {

                /*
                |--------------------------------------------------------------------------
                | جلب الكوبون مع قفل الصف
                |--------------------------------------------------------------------------
                */

                $coupon = Coupon::where(
                    'code',
                    $couponCode
                )
                    ->where(
                        'status_id',
                        CouponStatusConst::ACTIVE
                    )
                    ->lockForUpdate()
                    ->first();

                if (! $coupon) {
                    return [
                        'discount_amount' => 0.0,
                        'coupon' => null,
                    ];
                }

                /*
                |--------------------------------------------------------------------------
                | التحقق من تاريخ البداية
                |--------------------------------------------------------------------------
                */

                if (
                    $coupon->start_date &&
                    now()->lt($coupon->start_date)
                ) {
                    return [
                        'discount_amount' => 0.0,
                        'coupon' => null,
                    ];
                }

                /*
                |--------------------------------------------------------------------------
                | التحقق من تاريخ النهاية
                |--------------------------------------------------------------------------
                */

                if (
                    $coupon->end_date &&
                    now()->gt($coupon->end_date)
                ) {
                    return [
                        'discount_amount' => 0.0,
                        'coupon' => null,
                    ];
                }

                /*
                |--------------------------------------------------------------------------
                | الحد الأقصى للاستخدام الكلي
                |--------------------------------------------------------------------------
                */

                if ($coupon->usage_limit) {

                    $totalUsage = $coupon
                        ->usages()
                        ->lockForUpdate()
                        ->count();

                    if (
                        $totalUsage >=
                        $coupon->usage_limit
                    ) {
                        return [
                            'discount_amount' => 0.0,
                            'coupon' => null,
                        ];
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | الحد الأقصى للاستخدام لكل مستخدم
                |--------------------------------------------------------------------------
                */

                if (
                    $userId &&
                    $coupon->user_limit
                ) {

                    $userUsage = $coupon
                        ->usages()
                        ->where(
                            'user_id',
                            $userId
                        )
                        ->lockForUpdate()
                        ->count();

                    if (
                        $userUsage >=
                        $coupon->user_limit
                    ) {
                        return [
                            'discount_amount' => 0.0,
                            'coupon' => null,
                        ];
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | حساب الخصم
                |--------------------------------------------------------------------------
                */

                $discountAmount =
                    $this->calculateCouponDiscount(
                        $coupon,
                        $baseAmount
                    );

                return [
                    'discount_amount' => min(
                        $discountAmount,
                        $baseAmount
                    ),

                    'coupon' => $coupon,
                ];
            }
        );
    }

    /**
     * حساب خصم الكوبون.
     */
    private function calculateCouponDiscount(
        Coupon $coupon,
        float $baseAmount
    ): float {
        /*
        |--------------------------------------------------------------------------
        | خصم ثابت
        |--------------------------------------------------------------------------
        */

        if ($coupon->type === 'fixed') {
            return (float) $coupon->amount;
        }

        /*
        |--------------------------------------------------------------------------
        | خصم نسبة مئوية
        |--------------------------------------------------------------------------
        */

        $discount =
            $baseAmount *
            (
                (float) $coupon->percentage / 100
            );

        /*
        |--------------------------------------------------------------------------
        | الحد الأقصى للخصم
        |--------------------------------------------------------------------------
        */

        if ($coupon->max_discount) {
            $discount = min(
                $discount,
                (float) $coupon->max_discount
            );
        }

        return $discount;
    }

    /**
     * إنشاء سجلات ضرائب الحجز.
     */
    public function createBookingTaxes(
        int $bookingId,
        array $taxBreakdownDetails,
        ?int $currencyId = null,
        ?float $exchangeRate = null
    ): void {
        /*
        |--------------------------------------------------------------------------
        | العملة
        |--------------------------------------------------------------------------
        */

        $currencyId =
            $currencyId ??
            (int) (
                Setting::where(
                    'key',
                    'base_currency_id'
                )->value('value') ?? 1
            );

        /*
        |--------------------------------------------------------------------------
        | سعر الصرف
        |--------------------------------------------------------------------------
        */

        $exchangeRate =
            $exchangeRate ?: 1.0;

        $taxRecords = [];

        /*
        |--------------------------------------------------------------------------
        | تجهيز سجلات الضرائب
        |--------------------------------------------------------------------------
        */

        foreach (
            $taxBreakdownDetails as $taxDetail
        ) {

            $taxAmount = round(
                (
                    $taxDetail['amount']
                    ??
                    $taxDetail['tax_amount']
                    ??
                    0
                ) * $exchangeRate,
                2
            );

            /*
            |--------------------------------------------------------------------------
            | تجاهل الضريبة إذا كانت قيمتها صفر
            |--------------------------------------------------------------------------
            */

            if ($taxAmount <= 0) {
                continue;
            }

            $taxRecords[] = [
                'booking_id' => $bookingId,

                'tax_id' => $taxDetail['tax_id'],

                'tax_name' => $taxDetail['tax_name']
                    ??
                    $taxDetail['name']
                    ??
                    null,

                'tax_type_rate_or_amount' => $taxDetail['type']
                    ??
                    $taxDetail['tax_type']
                    ??
                    null,

                'tax_rate' => $taxDetail['rate']
                    ??
                    $taxDetail['tax_rate']
                    ??
                    null,

                'tax_amount' => $taxAmount,

                'currency_id' => $currencyId,

                'exchange_rate' => $exchangeRate,

                'base_tax_amount' => $exchangeRate > 0
                        ? round(
                            $taxAmount /
                            $exchangeRate,
                            2
                        )
                        : $taxAmount,

                'created_at' => now(),

                'updated_at' => now(),
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | الحفظ دفعة واحدة
        |--------------------------------------------------------------------------
        */

        if (! empty($taxRecords)) {
            BookingTax::insert($taxRecords);
        }
    }
}
