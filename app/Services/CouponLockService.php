<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\CouponUsage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Coupon Lock Service
 *
 * يمنع double-spend على الكوبونات عبر:
 * 1. قفل صف الكوبون في DB (lockForUpdate) داخل transaction
 * 2. لا يحرر القفل إلا بعد commit أو rollback
 * 3. التحقق من الاستخدام داخل نفس transaction
 */
class CouponLockService
{
    private const CACHE_LOCK_TIMEOUT_SECONDS = 30;
    private const MAX_RETRY_ATTEMPTS = 3;
    private const RETRY_DELAY_MICROSECONDS = 50000;

    /**
     * التحقق من صلاحية الكوبون مع قفل صف الكوبون
     *
     * @param string $couponCode كود الكوبون
     * @param float $orderAmount مبلغ الطلب الأصلي (قبل الخصم)
     * @param \App\Models\Trip $trip الرحلة المرتبطة
     * @param int $userId معرف المستخدم
     * @return array ['valid' => bool, 'message' => string, 'coupon' => ?Coupon]
     */
    public function validateCouponWithLock(string $couponCode, float $orderAmount, \App\Models\Trip $trip, int $userId): array
    {
        // 1. قفل cache كإجراء احترازي (advisory lock)
        $cacheLockAcquired = $this->acquireCacheLock($couponCode);
        if (!$cacheLockAcquired) {
            return [
                'valid' => false,
                'message' => 'ضغط عالي على النظام - يرجى المحاولة بعد لحظات',
            ];
        }

        try {
            // 2. التحقق داخل transaction مع lockForUpdate على صف الكوبون
            return DB::transaction(function () use ($couponCode, $orderAmount, $userId) {
                return $this->performValidationWithinTransaction($couponCode, $orderAmount, $userId);
            });
        } finally {
            // 3. تحرير قفل cache بعد انتهاء transaction
            $this->releaseCacheLock($couponCode);
        }
    }

    /**
     * تنفيذ التحقق الفعلي داخل transaction
     *
     * @param string $couponCode
     * @param float $orderAmount
     * @param int $userId
     * @return array
     */
    private function performValidationWithinTransaction(string $couponCode, float $orderAmount, int $userId): array
    {
        // قفل صف الكوبون لمنع القراءات المتزامنة (يحل Race Condition)
        $coupon = Coupon::where('code', $couponCode)->lockForUpdate()->first();

        if (!$coupon) {
            return ['valid' => false, 'message' => 'الكوبون غير موجود'];
        }

        // التحقق من الحالة (active)
        if (!$coupon->is_active) {
            return ['valid' => false, 'message' => 'الكوبون غير نشط'];
        }

        // التحقق من فترة الصلاحية
        if ($coupon->start_date && now()->lt($coupon->start_date)) {
            return ['valid' => false, 'message' => 'الكوبون لم يبدأ بعد'];
        }
        if ($coupon->end_date && now()->gt($coupon->end_date)) {
            return ['valid' => false, 'message' => 'انتهت صلاحية الكوبون'];
        }

        // التحقق من الحد الأقصى للاستخدام الكلي
        if ($coupon->usage_limit) {
            $totalUsageCount = CouponUsage::where('coupon_id', $coupon->id)
                ->lockForUpdate()
                ->count();

            if ($totalUsageCount >= $coupon->usage_limit) {
                return ['valid' => false, 'message' => 'تم استخدام الكوبون لأقصى عدد مرات مسموح به'];
            }
        }

        // التحقق من الحد الأقصى للاستخدام لكل مستخدم
        if ($coupon->max_usage_per_user) {
            $userUsageCount = CouponUsage::where('coupon_id', $coupon->id)
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->count();

            if ($userUsageCount >= $coupon->max_usage_per_user) {
                return ['valid' => false, 'message' => 'لقد استخدمت هذا الكوبون الحد الأقصى من المرات'];
            }
        }

        // حساب الخصم والتحقق من المبلغ النهائي
        $discountAmount = $this->calculateDiscountAmount($coupon, $orderAmount);
        $finalAmount = $orderAmount - $discountAmount;

        if ($finalAmount < 0) {
            return ['valid' => false, 'message' => 'الخصم يجعل المبلغ النهائي سالباً'];
        }

        // التحقق من الحد الأدنى للمبلغ الأصلي (وليس بعد الخصم)
        if ($coupon->minimum_amount && $orderAmount < $coupon->minimum_amount) {
            return [
                'valid' => false,
                'message' => 'مبلغ الطلب يجب أن يكون على الأقل ' . $coupon->minimum_amount,
            ];
        }

        return [
            'valid' => true,
            'coupon' => $coupon,
            'discount_amount' => round($discountAmount, 2),
            'final_amount' => round($finalAmount, 2),
        ];
    }

    /**
     * حساب مبلغ الخصم بناءً على نوع الكوبون
     */
    private function calculateDiscountAmount(Coupon $coupon, float $orderAmount): float
    {
        if ($coupon->discount_type === 'percentage') {
            $discount = $orderAmount * ($coupon->discount_value / 100);

            // تطبيق الحد الأقصى للخصم لو موجود
            if ($coupon->max_discount) {
                $discount = min($discount, $coupon->max_discount);
            }

            return $discount;
        }

        // fixed discount
        return min($coupon->discount_value, $orderAmount);
    }

    /**
     * محاولة اكتساب قفل cache كـ advisory lock
     */
    private function acquireCacheLock(string $couponCode): bool
    {
        $lockKey = $this->getCacheLockKey($couponCode);
        $attempts = 0;

        while ($attempts < self::MAX_RETRY_ATTEMPTS) {
            if (\Illuminate\Support\Facades\Cache::add($lockKey, now()->timestamp, self::CACHE_LOCK_TIMEOUT_SECONDS)) {
                return true;
            }

            $attempts++;
            if ($attempts < self::MAX_RETRY_ATTEMPTS) {
                usleep(self::RETRY_DELAY_MICROSECONDS * $attempts);
            }
        }

        Log::warning('Coupon cache lock acquisition failed', [
            'coupon_code' => $couponCode,
            'attempts' => $attempts,
        ]);

        return false;
    }

    /**
     * تحرير قفل cache
     */
    private function releaseCacheLock(string $couponCode): void
    {
        $lockKey = $this->getCacheLockKey($couponCode);
        \Illuminate\Support\Facades\Cache::forget($lockKey);
    }

    /**
     * مفتاح قفل cache للكوبون
     */
    private function getCacheLockKey(string $couponCode): string
    {
        return 'coupon_advisory_lock:' . sha1($couponCode);
    }
}
