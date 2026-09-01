<?php

namespace App\Services;

use App\Models\Commission;
use App\Models\ServiceCommission;
use App\Models\ProviderBranch;
use App\Models\BookingCommission;
use App\Models\Service;
use App\Models\ProviderService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Commission Service
 *
 * مسؤول عن:
 * - حساب عمولات الحجوزات
 * - إنشاء وربط أنواع العمولات بالخدمات والمزودين
 *
 * الأمان:
 * - Audit logging لكل التغييرات المالية
 * - Validation للقيم (منع القيم السالبة أو > 100%)
 * - Transactions للعمليات متعددة الخطوات
 */
class CommissionService
{
    private const MAX_PERCENTAGE_VALUE = 100;

    public function __construct(
        private AuditService $auditService
    ) {}

    /**
     * حساب عمولة الحجز بناءً على الفرع والمبلغ
     *
     * @param float $amount مبلغ الحجز (يجب أن يكون >= 0)
     * @param int|null $branchId معرف الفرع
     * @return array ['id' => ?int, 'value' => float, 'amount' => float, 'type' => ?string]
     */
    public function calculateBookingCommission(float $amount, ?int $branchId): array
    {
        //  Validation: منع المبالغ السالبة
        if ($amount < 0) {
            Log::warning('Negative amount passed to calculateBookingCommission', [
                'amount' => $amount,
                'branch_id' => $branchId,
            ]);
            return ['id' => null, 'value' => 0, 'amount' => 0, 'type' => null];
        }

        if (!$branchId) {
            return ['id' => null, 'value' => 0, 'amount' => 0, 'type' => null];
        }

        $branch = ProviderBranch::with('provider.serviceCommissions.commission')->find($branchId);
        if (!$branch) {
            return ['id' => null, 'value' => 0, 'amount' => 0, 'type' => null];
        }

        // البحث عن عمولة مناسبة للخدمة
        foreach ($branch->provider->serviceCommissions as $serviceCommission) {
            if ($this->isCommissionApplicable($serviceCommission, $amount)) {
                $commissionAmount = $this->calculateCommissionAmount(
                    $amount,
                    $serviceCommission->value,
                    $serviceCommission->commission?->commission_type
                );

                return [
                    'id' => $serviceCommission->commission_id,
                    'value' => $serviceCommission->value,
                    'amount' => round($commissionAmount, 2),
                    'type' => $serviceCommission->commission?->commission_type,
                ];
            }
        }

        // البحث عن عمولة عامة للحجوزات
        $bookingCommission = BookingCommission::with('commission')
            ->where('provider_id', $branch->provider->id)
            ->where(function ($query) use ($amount) {
                $query->whereNull('min_amount')->orWhere('min_amount', '<=', $amount);
            })
            ->where(function ($query) use ($amount) {
                $query->whereNull('max_amount')->orWhere('max_amount', '>=', $amount);
            })
            ->first();

        if ($bookingCommission) {
            $commissionAmount = $this->calculateCommissionAmount(
                $amount,
                $bookingCommission->value,
                $bookingCommission->commission?->commission_type
            );

            return [
                'id' => $bookingCommission->commission_id,
                'value' => $bookingCommission->value,
                'amount' => round($commissionAmount, 2),
                'type' => $bookingCommission->commission?->commission_type,
            ];
        }

        return ['id' => null, 'value' => 0, 'amount' => 0, 'type' => null];
    }

    /**
     * حساب مبلغ العمولة
     *
     * @param float $amount المبلغ الأساسي
     * @param float $value قيمة العمولة (نسبة أو مبلغ ثابت)
     * @param string|null $type نوع العمولة (percentage أو fixed)
     * @return float مبلغ العمولة
     */
    private function calculateCommissionAmount(float $amount, float $value, ?string $type): float
    {
        if ($type === 'percentage') {
            //  Validation: النسبة يجب أن تكون 0-100
            if ($value < 0 || $value > self::MAX_PERCENTAGE_VALUE) {
                Log::warning('Invalid commission percentage', ['value' => $value]);
                return 0;
            }
            return $amount * ($value / 100);
        }

        // fixed
        if ($value < 0) {
            Log::warning('Negative fixed commission value', ['value' => $value]);
            return 0;
        }

        return min($value, $amount);  // العمولة لا تتجاوز المبلغ
    }

    /**
     * التحقق مما إذا كانت العمولة قابلة للتطبيق
     */
    private function isCommissionApplicable(ServiceCommission $commission, float $amount): bool
    {
        return (!$commission->min_amount || $amount >= $commission->min_amount) &&
               (!$commission->max_amount || $amount <= $commission->max_amount);
    }

    /**
     * جلب عمولة خدمة معينة
     */
    public function getServiceCommission(int $serviceId, int $providerId): ?ServiceCommission
    {
        return ServiceCommission::where('service_id', $serviceId)
            ->where('provider_id', $providerId)
            ->with('commission')
            ->first();
    }

    /**
     *  إنشاء عمولة جديدة (مع audit + transaction)
     */
    public function createCommission(array $data): Commission
    {
        return DB::transaction(function () use ($data) {
            $commission = Commission::create([
                'commission_type' => $data['commission_type'],
                'calculation_level' => $data['calculation_level'],
                'description' => $data['description'] ?? null,
            ]);

            DB::afterCommit(function () use ($commission) {
                $this->auditService->log(
                    auth()->id(),
                    'create',
                    'commissions',
                    $commission->id,
                    null,
                    $commission->toArray()
                );
            });

            return $commission;
        });
    }

    /**
     *  ربط عمولة بخدمة (مع audit + validation)
     */
    public function assignCommissionToService(int $commissionId, int $providerId, int $serviceId, array $data): ServiceCommission
    {
        //  Validation للقيمة
        $this->validateCommissionValue($data['value'], $data['commission_type'] ?? null);

        //  التحقق من أن الخدمة تنتمي للمزود
        $service = Service::findOrFail($serviceId);
        if ($service->branch?->provider_id !== $providerId) {
            throw new \Exception('الخدمة لا تنتمي لهذا المزود');
        }

        return DB::transaction(function () use ($commissionId, $providerId, $serviceId, $data) {
            $serviceCommission = ServiceCommission::create([
                'provider_id' => $providerId,
                'service_id' => $serviceId,
                'commission_id' => $commissionId,
                'value' => $data['value'],
                'min_amount' => $data['min_amount'] ?? null,
                'max_amount' => $data['max_amount'] ?? null,
            ]);

            DB::afterCommit(function () use ($serviceCommission) {
                $this->auditService->log(
                    auth()->id(),
                    'assign_commission_service',
                    'service_commissions',
                    $serviceCommission->id,
                    null,
                    $serviceCommission->toArray()
                );
            });

            return $serviceCommission;
        });
    }

    /**
     *  ربط عمولة بالحجوزات (مع audit + validation)
     */
    public function assignCommissionToBooking(int $commissionId, int $providerId, array $data): BookingCommission
    {
        //  Validation للقيمة
        $this->validateCommissionValue($data['value'], $data['commission_type'] ?? null);

        //  التحقق من وجود المزود
        $provider = ProviderService::findOrFail($providerId);

        return DB::transaction(function () use ($commissionId, $providerId, $data) {
            $bookingCommission = BookingCommission::create([
                'provider_id' => $providerId,
                'commission_id' => $commissionId,
                'min_amount' => $data['min_amount'] ?? null,
                'max_amount' => $data['max_amount'] ?? null,
                'value' => $data['value'],
            ]);

            DB::afterCommit(function () use ($bookingCommission) {
                $this->auditService->log(
                    auth()->id(),
                    'assign_commission_booking',
                    'booking_commissions',
                    $bookingCommission->id,
                    null,
                    $bookingCommission->toArray()
                );
            });

            return $bookingCommission;
        });
    }

    /**
     *  التحقق من صحة قيمة العمولة
     *
     * @param float $value قيمة العمولة
     * @param string|null $type نوع العمولة
     * @throws \InvalidArgumentException للقيم غير الصالحة
     */
    private function validateCommissionValue(float $value, ?string $type): void
    {
        if ($value < 0) {
            throw new \InvalidArgumentException('قيمة العمولة يجب أن تكون أكبر من أو تساوي صفر');
        }

        if ($type === 'percentage' && $value > self::MAX_PERCENTAGE_VALUE) {
            throw new \InvalidArgumentException('نسبة العمولة يجب ألا تتجاوز 100%');
        }

        // التحقق من min < max لو كلاهما موجود
        // (يتم التحقق في الـ FormRequest)
    }
}
