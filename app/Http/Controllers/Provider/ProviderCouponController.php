<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Provider\StoreProviderCouponRequest;
use App\Http\Requests\Provider\UpdateProviderCouponRequest;
use App\Models\Coupon;
use App\Constants\CouponStatus;
use App\Models\CouponStatus as CouponStatusModel;
use App\Services\CouponService;
use App\Services\AuditService;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProviderCouponController extends Controller
{
    protected CouponService $couponService;
    protected AuditService $auditService;

    public function __construct(CouponService $couponService, AuditService $auditService)
    {
        $this->couponService = $couponService;
        $this->auditService = $auditService;
    }


    public function index(): View
    {
        $userId = auth()->id();

        $coupons = Coupon::where('user_id', $userId)
            ->latest()
            ->paginate(15);

        $allCoupons = Coupon::where('user_id', $userId)->get();
        $statusCounts = [
            'total'    => $allCoupons->count(),
            'active'   => $allCoupons->where('status_id', CouponStatus::ACTIVE)->count(),
            'expired'  => $allCoupons->where('status_id', CouponStatus::EXPIRED)->count(),
        ];

        $couponStatuses = CouponStatusModel::orderBy('id')->get();
        return view('provider.coupons.index', compact('coupons', 'statusCounts', 'couponStatuses'));    }


    public function create(): View
    {
        return view('provider.coupons.create');
    }


    public function store(StoreProviderCouponRequest $request): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $coupon = $this->couponService->createCoupon($request->validated(), auth()->id());

            $couponId = $coupon->id;
            $couponData = $coupon->toArray();
            DB::afterCommit(function () use ($couponId, $couponData) {
                $this->auditService->log(
                    auth()->id(),
                    'create',
                    'coupons',
                    $couponId,
                    null,
                    $couponData
                );
            });

            DB::commit();

            return redirect()->route('provider.coupons.index')
                ->with('success', 'تم إنشاء الكوبون بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Provider coupon store failed: ' . $e->getMessage());
            return back()->withInput()
                ->with('error', 'حدث خطأ أثناء إنشاء الكوبون. يرجى المحاولة مرة أخرى.');
        }
    }

    public function edit(string $uuid): View|RedirectResponse
    {
        $coupon = Coupon::where('uuid', $uuid)
            ->where('user_id', auth()->id())
            ->first();

        if (!$coupon) {
            return redirect()->route('provider.coupons.index')
                ->with('error', 'الكوبون غير موجود أو لا ينتمي لك');
        }

        return view('provider.coupons.edit', compact('coupon'));
    }


    public function update(UpdateProviderCouponRequest $request, string $uuid): RedirectResponse
    {
        $coupon = Coupon::where('uuid', $uuid)
            ->where('user_id', auth()->id())
            ->first();

        if (!$coupon) {
            return redirect()->route('provider.coupons.index')
                ->with('error', 'الكوبون غير موجود أو لا ينتمي لك');
        }

        DB::beginTransaction();
        try {
            $oldData = $coupon->toArray();
            $result = $this->couponService->updateCoupon($coupon, $request->validated());

            if (!$result['success']) {
                DB::rollBack();
                return back()->withInput()->with('error', $result['message']);
            }

            $newData = $coupon->fresh()->toArray();
            $changes = array_diff_assoc($newData, $oldData);

            if (!empty($changes)) {
                $couponId = $coupon->id;
                $oldSubset = array_intersect_key($oldData, $changes);
                DB::afterCommit(function () use ($couponId, $oldSubset, $changes) {
                    $this->auditService->log(
                        auth()->id(),
                        'update',
                        'coupons',
                        $couponId,
                        $oldSubset,
                        $changes
                    );
                });
            }

            DB::commit();

            return redirect()->route('provider.coupons.index')
                ->with('success', $result['message']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Provider coupon update failed: ' . $e->getMessage());
            return back()->withInput()
                ->with('error', 'حدث خطأ أثناء تحديث الكوبون. يرجى المحاولة مرة أخرى.');
        }
    }


    public function destroy(string $uuid): RedirectResponse
    {
        $coupon = Coupon::where('uuid', $uuid)
            ->where('user_id', auth()->id())
            ->first();

        if (!$coupon) {
            return redirect()->route('provider.coupons.index')
                ->with('error', 'الكوبون غير موجود أو لا ينتمي لك');
        }

        if ($coupon->trips()->exists()) {
            return redirect()->route('provider.coupons.index')
                ->with('error', 'لا يمكن حذف هذا الكوبون لأنه مرتبط برحلات');
        }

        if ($coupon->usages()->exists()) {
            return redirect()->route('provider.coupons.index')
                ->with('error', 'لا يمكن حذف هذا الكوبون لأنه تم استخدامه بالفعل');
        }

        DB::beginTransaction();
        try {
            $oldData = $coupon->toArray();

            $couponId = $coupon->id;
            $coupon->delete();

            DB::afterCommit(function () use ($couponId, $oldData) {
                $this->auditService->log(
                    auth()->id(),
                    'delete',
                    'coupons',
                    $couponId,
                    $oldData,
                    null
                );
            });

            DB::commit();

            return redirect()->route('provider.coupons.index')
                ->with('success', 'تم حذف الكوبون بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Provider coupon destroy failed: ' . $e->getMessage());
            return back()->with('error', 'تعذر إتمام عملية الحذف. يرجى المحاولة لاحقاً.');
        }
    }
}
