<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\CouponTrip;
use App\Models\Trip;
use App\Services\CouponLockService;
use App\Constants\CouponStatus as CouponStatusConst;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

/**
 * Coupon Service
 * 
 * This service handles all coupon-related operations including validation,
 * application, usage tracking, and security considerations.
 * 
 * Security features:
 * - Atomic coupon usage with database locking
 * - Comprehensive validation and fraud prevention
 * - Detailed usage tracking and audit logging
 * - Rate limiting and concurrency protection
 */
class CouponService
{
    public function __construct(
        private CouponLockService $couponLockService,
        private AuditService $auditService
    ) {}

    public function createCoupon(array $data, int $userId): Coupon
    {
        $data['user_id'] = $userId;
        
        if (!isset($data['status_id'])) {
            $data['status_id'] = CouponStatusConst::ACTIVE;
        }

        return Coupon::create($data);
    }
    /**
     * Validate coupon with concurrency protection
     * 
     * This method validates coupon applicability while preventing
     * concurrent usage through dedicated locking service.
     * 
     * Why use dedicated lock service: To ensure thread-safe validation
     * and prevent race conditions in high-traffic scenarios.
     * 
     * @param string $couponCode Coupon code to validate
     * @param float $orderAmount Order total amount
     * @param Trip $trip Trip model for validation
     * @param int $userId User ID for validation
     * @return array Validation result with detailed information
     */
    public function validateCoupon(string $couponCode, float $orderAmount, Trip $trip, int $userId): array
    {
        // Use CouponLockService for concurrent usage prevention
        // Why: Ensure thread-safe validation and prevent race conditions
        return $this->couponLockService->validateCouponWithLock($couponCode, $orderAmount, $trip, $userId);
    }

    /**
     * Apply discount to order amount
     * 
     * This method calculates the discounted amount based on coupon
     * configuration and ensures proper discount application.
     * 
     * @param float $originalAmount Original order amount
     * @param Coupon $coupon Coupon model with discount configuration
     * @return float Discounted amount
     */
    public function applyDiscount(float $originalAmount, Coupon $coupon): float
    {
        // Calculate discount based on coupon type
        // Why: Support both fixed amount and percentage-based discounts
        $discountAmount = $coupon->type === 'fixed' 
            ? $coupon->amount 
            : $originalAmount * ($coupon->percentage / 100);
        
        // Apply discount with minimum amount protection
        // Why: Ensure discounted amount doesn't go below zero
        $discountedAmount = max(0, $originalAmount - $discountAmount);
        
        Log::info('Coupon discount applied', [
            'coupon_id' => $coupon->id,
            'coupon_code' => $coupon->code,
            'original_amount' => $originalAmount,
            'discount_amount' => $discountAmount,
            'final_amount' => $discountedAmount,
        ]);
        
        return $discountedAmount;
    }


    /**
     * Find valid coupon with comprehensive checks
     * 
     * This method retrieves a coupon by code and performs all necessary
     * validations to ensure it can be applied to the current order.
     * 
     * Validation checks include:
     * - Coupon existence and active status
     * - Validity period (start and end dates)
     * - Minimum order amount requirement
     * - Total usage limit
     * - Per-user usage limit
     * 
     * @param string $couponCode Coupon code to find and validate
     * @param int|null $userId Optional user ID for per-user validation
     * @param float|null $amount Optional order amount for minimum amount validation
     * @return Coupon|null Valid coupon model or null if invalid
     */
    /**
     * Find and validate a coupon
     *
     * الأمان:
     * - lockForUpdate داخل transaction (القفل يُحرر بعد commit/rollback)
     * - التحقق من الحدود داخل transaction
     *
     * @param string $couponCode Coupon code to find and validate
     * @param int|null $userId Optional user ID for per-user validation
     * @param float|null $amount Optional order amount for minimum amount validation
     * @return Coupon|null Valid coupon model or null if invalid
     */
    public function findValidCoupon(string $couponCode, ?int $userId = null, ?float $amount = null): ?Coupon
    {
        return DB::transaction(function () use ($couponCode, $userId, $amount) {
            $coupon = Coupon::where('code', $couponCode)
                ->where('status_id', CouponStatusConst::ACTIVE)
                ->where(function ($query) {
                    $query->whereNull('start_date')->orWhere('start_date', '<=', now());
                })
                ->where(function ($query) {
                    $query->whereNull('end_date')->orWhere('end_date', '>=', now());
                })
                ->lockForUpdate()
                ->first();

            if (!$coupon) {
                return null;
            }

            //  التحقق من الحد الأدنى (قبل الخصم)
            if ($amount !== null && $coupon->minimum_amount && $amount < $coupon->minimum_amount) {
                return null;
            }

            // التحقق من الحد الأقصى للاستخدام
            if ($coupon->usage_limit && $coupon->usages()->count() >= $coupon->usage_limit) {
                return null;
            }

            // التحقق من استخدام المستخدم
            if ($userId && $coupon->max_usage_per_user) {
                $userUsageCount = $coupon->usages()->where('user_id', $userId)->count();
                if ($userUsageCount >= $coupon->max_usage_per_user) {
                    return null;
                }
            }

            return $coupon;
        });
    }

    /**
     * Get trips applicable for coupon usage
     * 
     * This method determines which trips from a collection are eligible
     * for the coupon based on coupon configuration.
     * 
     * @param Coupon $coupon Coupon model with applicability rules
     * @param array $trips Array of trip models to check
     * @return array Array of applicable trip models
     */
    public function getCouponApplicableTrips(Coupon $coupon, array $trips): array
    {
        // If coupon applies to all trips, return all
        // Why: Support both universal and trip-specific coupons
        if ($coupon->applicable_to_all_trips) {
            return $trips;
        }
        
        // Filter trips based on coupon applicability rules
        $applicableTrips = [];
        foreach ($trips as $trip) {
            if ($this->isTripApplicableForCoupon($coupon, $trip)) {
                $applicableTrips[] = $trip;
            }
        }
        
        return $applicableTrips;
    }

    /**
     * Validate trip applicability for coupon
     * 
     * This method checks if a specific trip meets the coupon's
     * applicability criteria based on cities and vehicle types.
     * 
     * Business logic:
     * - Validates departure/destination city restrictions
     * - Validates vehicle type restrictions
     * - Supports both universal and targeted coupons
     * 
     * @param Coupon $coupon Coupon model with applicability rules
     * @param Trip $trip Trip model to validate
     * @return bool True if trip meets all applicability criteria
     */
    private function isTripApplicableForCoupon(Coupon $coupon, Trip $trip): bool
    {
        // Check if trip is in allowed cities
        // Why: Support location-based coupon restrictions
        if ($coupon->applicable_cities && !empty($coupon->applicable_cities)) {
            $allowedCities = is_array($coupon->applicable_cities) 
                ? $coupon->applicable_cities 
                : json_decode($coupon->applicable_cities, true);
            
            if (!in_array($trip->departure_city, $allowedCities) && 
                !in_array($trip->destination_city, $allowedCities)) {
                return false;
            }
        }
        
        // Check if trip is in allowed vehicle types
        // Why: Support vehicle-type based coupon restrictions
        if ($coupon->applicable_vehicle_types && !empty($coupon->applicable_vehicle_types)) {
            $allowedVehicleTypes = is_array($coupon->applicable_vehicle_types)
                ? $coupon->applicable_vehicle_types
                : json_decode($coupon->applicable_vehicle_types, true);
            
            if (!in_array($trip->vehicle_id, $allowedVehicleTypes)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Record coupon usage for tracking and analytics
     * 
     * This method creates a detailed record of coupon usage for
     * audit purposes and business intelligence.
     * 
     * @param Coupon $coupon Coupon model that was used
     * @param int $userId User ID who used the coupon
     * @param int $bookingId Booking ID associated with usage
     * @param float $discountAmount Amount of discount applied
     * @return void
     */
    public function recordCouponUsage(Coupon $coupon, int $userId, int $bookingId, float $discountAmount): void
    {
        CouponUsage::create([
            'coupon_id' => $coupon->id,
            'user_id' => $userId,
            'booking_id' => $bookingId,
            'discount_amount' => $discountAmount,
            'used_at' => now(),
        ]);
        
        Log::info('Coupon usage recorded', [
            'coupon_id' => $coupon->id,
            'coupon_code' => $coupon->code,
            'user_id' => $userId,
            'booking_id' => $bookingId,
            'discount_amount' => $discountAmount,
        ]);
    }

    /**
     * Mark coupon as used with atomic locking
     * 
     * This method safely increments the coupon usage counter with
     * database locking to prevent concurrent usage issues.
     * 
     * Security considerations:
     * - Must be called within DB::transaction for effective locking
     * - Uses lockForUpdate() to prevent race conditions
     * - Validates usage limits before incrementing
     * 
     * @param Coupon $coupon Coupon model to mark as used
     * @throws \Exception When coupon cannot be marked as used
     */
    /**
     * Mark coupon as used with atomic locking
     *
     * الأمان:
     * - يفتح transaction خاص لضمان atomicity
     * - lockForUpdate على صف الكوبون لمنع double-spend
     * - يتحقق من الحدود داخل transaction
     *
     * @param Coupon $coupon Coupon model to mark as used
     * @throws \Exception When coupon cannot be marked as used
     */
    public function markAsUsed(Coupon $coupon): void
    {
        DB::transaction(function () use ($coupon) {
            //  قفل صف الكوبون داخل transaction (القفل يُحرر بعد commit/rollback)
            $lockedCoupon = Coupon::where('id', $coupon->id)
                ->lockForUpdate()
                ->first();

            if (!$lockedCoupon) {
                throw new \Exception('Coupon not found');
            }

            // Validate that coupon hasn't exceeded usage limits
            if ($lockedCoupon->usage_limit && $lockedCoupon->usages()->count() >= $lockedCoupon->usage_limit) {
                throw new \Exception('Coupon has reached maximum usage limit');
            }

            $lockedCoupon->increment('usage_count');
            // Usage is recorded in coupon_usages table
            Log::info('Coupon marked as used', [
                'coupon_id' => $lockedCoupon->id,
                'coupon_code' => $lockedCoupon->code,
                'usage_count' => $lockedCoupon->usages()->count(),
                'usage_limit' => $lockedCoupon->usage_limit,
            ]);
        });
    }

    /**
     * Retrieve comprehensive coupon usage statistics
     * 
     * This method aggregates coupon usage data for analytics and
     * business intelligence purposes.
     * 
     * Statistics include:
     * - Total usage count
     * - Unique user count
     * - Total discount amount given
     * - Average discount per use
     * - Usage trend by date
     * - Remaining usage allowance
     * - Usage percentage of limit
     * 
     * @param Coupon $coupon Coupon model to analyze
     * @return array Comprehensive usage statistics with trend data
     */
    public function getCouponStatistics(Coupon $coupon): array
    {
        $totalUsages = $coupon->usages()->count();
        $uniqueUsers = $coupon->usages()->distinct('user_id')->count('user_id');
        $totalDiscount = $coupon->usages()->sum('discount_amount');
        
        // Get usage by date for trend analysis
        $usageByDate = $coupon->usages()
            ->selectRaw('DATE(used_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        
        return [
            'total_usages' => $totalUsages,
            'unique_users' => $uniqueUsers,
            'total_discount_given' => $totalDiscount,
            'average_discount_per_use' => $totalUsages > 0 ? $totalDiscount / $totalUsages : 0,
            'usage_by_date' => $usageByDate->toArray(),
            'remaining_uses' => max(0, $coupon->usage_limit - $coupon->couponUsages()->count()),
            'usage_percentage' => $coupon->usage_limit > 0 
                ? ($coupon->couponUsages()->count() / $coupon->usage_limit) * 100 
                : 0,
        ];
    }

    /**
     * Deactivate coupon with audit logging
     * 
     * This method safely deactivates a coupon with proper validation
     * and comprehensive logging for audit purposes.
     * 
     * Security considerations:
     * - Audit logging for compliance
     * - Error handling for failed deactivation
     * - Records deactivation reason and performer
     * 
     * @param Coupon $coupon Coupon model to deactivate
     * @param string $reason Reason for deactivation
     * @return bool True if deactivation successful, false otherwise
     */
    public function deactivateCoupon(Coupon $coupon, string $reason, ?int $actorId = null): bool
    {
        try {
            $oldData = $coupon->toArray();

            $coupon->update([
                'status_id' => CouponStatusConst::INACTIVE,
            ]);

            //  Audit في afterCommit
            DB::afterCommit(function () use ($coupon, $oldData, $reason, $actorId) {
                $this->auditService->log(
                    $actorId ?? auth()->id(),
                    'deactivate',
                    'coupons',
                    $coupon->id,
                    $oldData,
                    array_merge($coupon->fresh()->toArray(), ['deactivation_reason' => $reason])
                );

                Log::info('Coupon deactivated', [
                    'coupon_id' => $coupon->id,
                    'coupon_code' => $coupon->code,
                    'reason' => $reason,
                    'deactivated_by' => $actorId,
                ]);
            });

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to deactivate coupon', [
                'coupon_id' => $coupon->id,
                'coupon_code' => $coupon->code,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Update coupon with conditional field restrictions
     * 
     * This method updates a coupon while enforcing restrictions based
     * on whether the coupon has been used or not.
     * 
     * Restrictions:
     * - If coupon has been used, only allow updating status and end_date
     * - If coupon has not been used, allow updating all fields
     * - Validate end_date to be in the future
     * - Validate usage_limit to not be less than current usage_count
     * 
     * @param Coupon $coupon Coupon model to update
     * @param array $data Data to update the coupon with
     * @return array Result of the update operation with success status and message
     */
    public function updateCoupon(Coupon $coupon, array $data): array
    {
        $isUsed = $coupon->usage_count > 0;

        $allowedFields = $isUsed
            ? ['status_id', 'end_date']
            : ['code', 'percentage', 'start_date', 'end_date', 'status_id', 'minimum_amount', 'usage_limit', 'max_usage_per_user'];

        $data = array_intersect_key($data, array_flip($allowedFields));

        if (empty($data)) {
            return ['success' => false, 'message' => 'لا يمكن تعديل الكوبون بعد استخدامه'];
        }

        if (isset($data['end_date']) && Carbon::parse($data['end_date'])->lte(now())) {
            return ['success' => false, 'message' => 'تاريخ الانتهاء يجب أن يكون بعد اليوم'];
        }
        $actualUsageCount = $coupon->usages()->count();
        if (isset($data['usage_limit']) && $data['usage_limit'] < $actualUsageCount) {
            return ['success' => false, 'message' => "الحد الأقصى للاستخدام لا يمكن أن يقل عن عدد الاستخدامات الفعلية ({$actualUsageCount})"];
        }

        try {
            $oldData = $coupon->toArray();

            $coupon->update($data);

            //  Audit في afterCommit
            DB::afterCommit(function () use ($coupon, $oldData) {
                $this->auditService->log(
                    auth()->id(),
                    'update',
                    'coupons',
                    $coupon->id,
                    $oldData,
                    $coupon->fresh()->toArray()
                );
            });

            return ['success' => true, 'message' => 'تم تحديث الكوبون بنجاح'];
        } catch (\Exception $e) {
            Log::error('Failed to update coupon', [
                'coupon_id' => $coupon->id,
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'message' => 'فشل تحديث الكوبون'];
        }
    }
}
