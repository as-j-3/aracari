<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CommissionService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommissionController extends Controller
{
    use ApiResponse;

    protected CommissionService $commissionService;

    public function __construct(CommissionService $commissionService)
    {
        $this->commissionService = $commissionService;
    }


    public function index(): JsonResponse
    {
        $isAdmin = auth()->user()->isAdmin();
        $providerId = auth()->user()->providerService?->id;

        if (!$isAdmin && !$providerId) {
            return $this->error('غير مصرح لك بعرض العمولات', 403);
        }

        $commissions = \App\Models\Commission::with([
            'serviceCommissions.provider',
            'serviceCommissions.service',
            'serviceCommissions.commission',
            'bookingCommissions.provider',
            'bookingCommissions.commission'
        ])
        ->when(!$isAdmin, function ($query) use ($providerId) {
            $query->whereHas('serviceCommissions', function ($q) use ($providerId) {
                $q->where('provider_id', $providerId);
            })
            ->orWhereHas('bookingCommissions', function ($q) use ($providerId) {
                $q->where('provider_id', $providerId);
            });
        })
        ->paginate(20);  

        return $this->success($commissions, 'تم جلب أنواع العمولات بنجاح');
    }


    public function store(Request $request): JsonResponse
    {
        if (!auth()->user()->isAdmin()) {
            return $this->error('غير مصرح لك بإنشاء أنواع العمولات', 403);
        }

        $data = $request->validate([
            'commission_type' => 'required|in:fixed,percentage',
            'calculation_level' => 'required|in:service,booking',
            'description' => 'nullable|string',
        ]);

        $commission = $this->commissionService->createCommission($data);

        return $this->success($commission, 'تم إنشاء نوع العمولة بنجاح');
    }


    public function show(int $id): JsonResponse
    {
        $isAdmin = auth()->user()->isAdmin();
        $providerId = auth()->user()->providerService?->id;

        $commission = \App\Models\Commission::with([
            'serviceCommissions.provider',
            'serviceCommissions.service',
            'serviceCommissions.commission',
            'bookingCommissions.provider',
            'bookingCommissions.commission'
        ])->find($id);

        if (!$commission) {
            return $this->notFound('نوع العمولة غير موجود');
        }

        if (!$isAdmin) {
            if (!$providerId) {
                return $this->error('غير مصرح لك بعرض هذا النوع من العمولات', 403);
            }

            $hasAccess = $commission->serviceCommissions->contains('provider_id', $providerId) ||
                         $commission->bookingCommissions->contains('provider_id', $providerId);

            if (!$hasAccess) {
                return $this->error('غير مصرح لك بعرض هذا النوع من العمولات', 403);
            }
        }

        return $this->success($commission);
    }


    public function assignToService(Request $request): JsonResponse
    {
        if (!auth()->user()->isAdmin()) {
            return $this->error('غير مصرح لك بربط العمولات بالخدمات', 403);
        }

        $data = $request->validate([
            'commission_id' => 'required|exists:commissions,id',
            'provider_id' => 'required|exists:provider_services,id',
            'service_id' => 'required|exists:services,id',
            'value' => 'required|numeric|min:0',
            'min_amount' => 'nullable|numeric|min:0',
            'max_amount' => 'nullable|numeric|min:0',
        ]);

        $serviceCommission = $this->commissionService->assignCommissionToService(
            $data['commission_id'],
            $data['provider_id'],
            $data['service_id'],
            $data
        );

        return $this->success($serviceCommission, 'تم ربط العمولة بالخدمة بنجاح');
    }


    public function assignToBooking(Request $request): JsonResponse
    {
        if (!auth()->user()->isAdmin()) {
            return $this->error('غير مصرح لك بربط العمولات بالحجوزات', 403);
        }

        $data = $request->validate([
            'commission_id' => 'required|exists:commissions,id',
            'provider_id' => 'required|exists:provider_services,id',
            'value' => 'required|numeric|min:0',
            'min_amount' => 'nullable|numeric|min:0',
            'max_amount' => 'nullable|numeric|min:0',
        ]);

        $bookingCommission = $this->commissionService->assignCommissionToBooking(
            $data['commission_id'],
            $data['provider_id'],
            $data
        );

        return $this->success($bookingCommission, 'تم ربط العمولة بالحجوزات بنجاح');
    }

    public function calculate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:0',
            'branch_id' => 'nullable|exists:provider_branches,id',
        ]);

        $commission = $this->commissionService->calculateBookingCommission(
            $data['amount'],
            $data['branch_id']
        );

        return $this->success($commission, 'تم حساب العمولة بنجاح');
    }
}
