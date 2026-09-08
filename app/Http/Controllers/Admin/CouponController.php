<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Coupon\StoreCouponRequest;
use App\Http\Requests\Web\Coupon\UpdateCouponRequest;
use App\Http\Requests\Web\Coupon\IndexCouponRequest;
use App\Http\Resources\Admin\CouponResource;
use App\Repositories\Contracts\CouponRepositoryInterface;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CouponController extends Controller
{
    public function __construct(
        private AuditService $auditService,
        private CouponRepositoryInterface $couponRepository
    ) {
        $this->middleware('require.admin');
    }

    /*
    |--------------------------------------------------------------------------
    | واجهة برمجية (API) - استجابات JSON
    |--------------------------------------------------------------------------
    |
    | تم تحويل الدوال الآتية لتعمل كهيئة API باستخدام success()/error()
    | والمنطق نفسه في دوال الويب القديمة (محفوظة بالأسفل كتعليقات).
    |
    */

    public function index(IndexCouponRequest $request): JsonResponse
    {
        $coupons = $this->couponRepository->search(
            $request->validated(),
            $request->validated('per_page') ?? 15
        );

        return $this->success(
            CouponResource::collection($coupons),
            'تم استرجاع الكوبونات بنجاح'
        );
    }

    public function store(StoreCouponRequest $request): JsonResponse
    {
        try {
            $coupon = $this->couponRepository->create($request->validated(), auth()->id());

            DB::afterCommit(function () use ($coupon) {
                $this->auditService->log(
                    auth()->id(),
                    'create',
                    'coupons',
                    $coupon->id,
                    null,
                    $coupon->fresh()->toArray()
                );
            });

            return $this->success(
                new CouponResource($coupon->fresh()),
                'تم إضافة الكوبون بنجاح'
            );
        } catch (\Exception $e) {
            Log::error('Coupon store failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(
                'فشل إضافة الكوبون. يرجى المحاولة مرة أخرى.',
                null,
                422
            );
        }
    }

    public function update(UpdateCouponRequest $request, string $coupon): JsonResponse
    {
        $couponModel = $this->couponRepository->findByUuid($coupon);

        if (! $couponModel) {
            return $this->error(
                'الكوبون غير موجود',
                null,
                404
            );
        }

        $oldData = $couponModel->toArray();

        try {
            $result = $this->couponRepository->update($couponModel, $request->validated());

            if (! $result['success']) {
                return $this->error(
                    $result['message'],
                    null,
                    422
                );
            }

            DB::afterCommit(function () use ($couponModel, $oldData) {
                $this->auditService->log(
                    auth()->id(),
                    'update',
                    'coupons',
                    $couponModel->id,
                    $oldData,
                    $couponModel->fresh()->toArray()
                );
            });

            return $this->success(
                new CouponResource($couponModel->fresh()),
                $result['message']
            );
        } catch (\Exception $e) {
            Log::error('Coupon update failed', [
                'error' => $e->getMessage(),
                'id'    => $couponModel->id,
            ]);

            return $this->error(
                'حدث خطأ غير متوقع أثناء تحديث الكوبون.',
                null,
                422
            );
        }
    }

    public function destroy(string $coupon): JsonResponse
    {
        $couponModel = $this->couponRepository->findByUuid($coupon);

        if (! $couponModel) {
            return $this->error(
                'الكوبون غير موجود',
                null,
                404
            );
        }

        if ($this->couponRepository->hasUsages($couponModel)) {
            return $this->error(
                'لا يمكن حذف كوبون مستخدم في حجوزات',
                null,
                422
            );
        }

        $couponId = $couponModel->id;
        $oldData = $couponModel->toArray();

        try {
            DB::beginTransaction();

            $this->couponRepository->delete($couponModel);

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

            return $this->success(
                null,
                'تم حذف الكوبون بنجاح'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Coupon destroy failed', [
                'error' => $e->getMessage(),
                'id'    => $couponId,
            ]);

            return $this->error(
                'تعذر إتمام عملية الحذف. يرجى المحاولة لاحقاً.',
                null,
                500
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | دوال الويب القديمة (محفوظة كتعليقات وليست محذوفة)
    |--------------------------------------------------------------------------
    |
    | كانت تعيد صفحات Blade عبر return view() / redirect().
    | تم استبدالها بنسخ JSON أعلاه. نفس المنطق والتحقق.
    |
    */

    /*
    public function index(IndexCouponRequest $request): View
    {
        $query = Coupon::with('status');

        if ($search = $request->get('search')) {
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search);
            $query->where(function (Builder $q) use ($escaped) {
                $q->where('code', 'like', "%{$escaped}%")
                  ->orWhere('name', 'like', "%{$escaped}%")
                  ->orWhere('description', 'like', "%{$escaped}%");
            });
        }

        if ($statusId = $request->get('status_id')) {
            $query->where('status_id', $statusId);
        }
        if ($discountType = $request->get('discount_type')) {
            $query->where('discount_type', $discountType);
        }
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }
        if ($dateFrom = $request->get('date_from')) {
            $query->where('start_date', '>=', $dateFrom);
        }
        if ($dateTo = $request->get('date_to')) {
            $query->where('end_date', '<=', $dateTo);
        }

        $coupons = $query->latest('created_at')
            ->paginate($request->get('per_page', 15))
            ->withQueryString();

        $statuses = $this->getCouponStatuses();

        return view('admin.coupons.index', compact('coupons', 'statuses'));
    }

    public function create(): View
    {
        $statuses = $this->getCouponStatuses();
        return view('admin.coupons.create', compact('statuses'));
    }

    public function store(StoreCouponRequest $request): RedirectResponse
    {
        try {
            $coupon = $this->couponService->createCoupon($request->validated(), auth()->id());

            DB::afterCommit(function () use ($coupon) {
                $this->auditService->log(
                    auth()->id(),
                    'create',
                    'coupons',
                    $coupon->id,
                    null,
                    $coupon->fresh()->toArray()
                );
            });

            return redirect()->route('admin.coupons.index')
                ->with('success', 'تم إضافة الكوبون بنجاح');
        } catch (\Exception $e) {
            Log::error('Coupon store failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->back()
                ->withInput()
                ->with('error', 'فشل إضافة الكوبون. يرجى المحاولة مرة أخرى.');
        }
    }

    public function edit(Coupon $coupon): View
    {
        $statuses = $this->getCouponStatuses();
        return view('admin.coupons.edit', compact('coupon', 'statuses'));
    }

    public function update(UpdateCouponRequest $request, Coupon $coupon): RedirectResponse
    {
        $oldData = $coupon->toArray();

        try {
            $result = $this->couponService->updateCoupon($coupon, $request->validated());

            if (!$result['success']) {
                return back()->withInput()->with('error', $result['message']);
            }

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

            return redirect()->route('admin.coupons.index')
                ->with('success', $result['message']);
        } catch (\Exception $e) {
            Log::error('Coupon update failed', [
                'error' => $e->getMessage(),
                'id'    => $coupon->id,
            ]);
            return back()->withInput()
                ->with('error', 'حدث خطأ غير متوقع أثناء تحديث الكوبون.');
        }
    }

    public function destroy(Request $request, Coupon $coupon): JsonResponse|RedirectResponse
    {
        if ($coupon->couponUsages()->exists()) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'لا يمكن حذف كوبون مستخدم في حجوزات'], 422);
            }
            return redirect()->route('admin.coupons.index')
                ->with('error', 'لا يمكن حذف كوبون مستخدم في حجوزات');
        }

        $couponId = $coupon->id;
        $oldData = $coupon->toArray();

        try {
            DB::beginTransaction();

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

            return redirect()->route('admin.coupons.index')
                ->with('success', 'تم حذف الكوبون بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Coupon destroy failed', [
                'error' => $e->getMessage(),
                'id'    => $couponId,
            ]);
            return back()
                ->with('error', 'تعذر إتمام عملية الحذف. يرجى المحاولة لاحقاً.');
        }
    }
    */
}
