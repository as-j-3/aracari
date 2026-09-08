<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\CouponStatus\StoreCouponStatusRequest;
use App\Http\Requests\Web\CouponStatus\UpdateCouponStatusRequest;
use App\Http\Requests\Web\Lookup\IndexLookupRequest;
use App\Http\Resources\Admin\CouponStatusResource;
use App\Repositories\Contracts\CouponStatusRepositoryInterface;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CouponStatusController extends Controller
{
    public function __construct(
        private AuditService $auditService,
        private CouponStatusRepositoryInterface $couponStatusRepository
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

    public function index(IndexLookupRequest $request): JsonResponse
    {
        $statuses = $this->couponStatusRepository->search(
            $request->validated(),
            $request->validated('per_page') ?? 15
        );

        return $this->success(
            CouponStatusResource::collection($statuses),
            'تم استرجاع حالات الكوبون بنجاح'
        );
    }

    public function store(StoreCouponStatusRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $status = $this->couponStatusRepository->create($request->validated());

            DB::afterCommit(function () use ($status) {
                $this->auditService->log(
                    auth()->id(),
                    'create',
                    'coupon_statuses',
                    $status->id,
                    null,
                    $status->fresh()->toArray()
                );
            });

            DB::commit();

            return $this->success(
                new CouponStatusResource($status->fresh()),
                'تم إضافة حالة الكوبون بنجاح'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('CouponStatus store failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(
                'حدث خطأ أثناء إضافة الحالة. يرجى المحاولة مرة أخرى.',
                null,
                422
            );
        }
    }

    public function update(UpdateCouponStatusRequest $request, int $id): JsonResponse
    {
        $status = $this->couponStatusRepository->findById($id);

        if (! $status) {
            return $this->error(
                'حالة الكوبون غير موجودة',
                null,
                404
            );
        }

        try {
            DB::beginTransaction();

            $oldValues = $status->toArray();
            $this->couponStatusRepository->update($id, $request->validated());

            DB::afterCommit(function () use ($status, $oldValues) {
                $this->auditService->log(
                    auth()->id(),
                    'update',
                    'coupon_statuses',
                    $status->id,
                    $oldValues,
                    $status->fresh()->toArray()
                );
            });

            DB::commit();

            return $this->success(
                new CouponStatusResource($status->fresh()),
                'تم تحديث حالة الكوبون بنجاح'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('CouponStatus update failed', [
                'error' => $e->getMessage(),
                'id'    => $status->id,
            ]);

            return $this->error(
                'حدث خطأ أثناء تحديث الحالة. يرجى المحاولة مرة أخرى.',
                null,
                422
            );
        }
    }

    public function destroy(int $id): JsonResponse
    {
        $status = $this->couponStatusRepository->findById($id);

        if (! $status) {
            return $this->error(
                'حالة الكوبون غير موجودة',
                null,
                404
            );
        }

        if ($this->couponStatusRepository->hasCoupons($id)) {
            return $this->error(
                'لا يمكن حذف هذه الحالة لوجود كوبونات مرتبطة بها',
                null,
                422
            );
        }

        try {
            DB::beginTransaction();

            $recordId = $status->id;
            $oldValues = $status->toArray();
            $this->couponStatusRepository->delete($recordId);

            DB::afterCommit(function () use ($recordId, $oldValues) {
                $this->auditService->log(
                    auth()->id(),
                    'delete',
                    'coupon_statuses',
                    $recordId,
                    $oldValues,
                    null
                );
            });

            DB::commit();

            return $this->success(
                null,
                'تم حذف حالة الكوبون بنجاح'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('CouponStatus destroy failed', [
                'error' => $e->getMessage(),
                'id'    => $recordId,
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
    public function index(IndexLookupRequest $request): View
    {
        $query = CouponStatus::query();

        if ($search = $request->get('search')) {
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search);
            $query->where(fn (Builder $q) => $q->where('name', 'like', "%{$escaped}%"));
        }

        $statuses = $query->latest('created_at')
            ->paginate($request->get('per_page', 15))
            ->withQueryString();

        return view('admin.lookups.coupon-statuses.index', compact('statuses'));
    }

    public function create(): View
    {
        return view('admin.lookups.coupon-statuses.create');
    }

    public function store(StoreCouponStatusRequest $request): RedirectResponse
    {
        try {
            DB::beginTransaction();

            $status = CouponStatus::create($request->validated());

            DB::afterCommit(function () use ($status) {
                $this->auditService->log(
                    auth()->id(),
                    'create',
                    'coupon_statuses',
                    $status->id,
                    null,
                    $status->fresh()->toArray()
                );

                cache()->forget('coupon-statuses-list');
            });

            DB::commit();

            return redirect()->route('admin.lookups.coupon-statuses.index')
                ->with('success', 'تم إضافة حالة الكوبون بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('CouponStatus store failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->withInput()
                ->with('error', 'حدث خطأ أثناء إضافة الحالة. يرجى المحاولة مرة أخرى.');
        }
    }

    public function edit(int $id): View
    {
        $status = CouponStatus::findOrFail($id);
        return view('admin.lookups.coupon-statuses.edit', compact('status'));
    }

    public function update(UpdateCouponStatusRequest $request, int $id): RedirectResponse
    {
        $status = CouponStatus::findOrFail($id);

        try {
            DB::beginTransaction();

            $oldValues = $status->toArray();
            $status->update($request->validated());

            DB::afterCommit(function () use ($status, $oldValues) {
                $this->auditService->log(
                    auth()->id(),
                    'update',
                    'coupon_statuses',
                    $status->id,
                    $oldValues,
                    $status->fresh()->toArray()
                );

                cache()->forget('coupon-statuses-list');
            });

            DB::commit();

            return redirect()->route('admin.lookups.coupon-statuses.index')
                ->with('success', 'تم تحديث حالة الكوبون بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('CouponStatus update failed', [
                'error' => $e->getMessage(),
                'id'    => $status->id,
            ]);
            return back()->withInput()
                ->with('error', 'حدث خطأ أثناء تحديث الحالة. يرجى المحاولة مرة أخرى.');
        }
    }

    public function destroy(Request $request, int $id)
    {
        $status = CouponStatus::findOrFail($id);

        if ($status->coupons()->exists()) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'لا يمكن حذف هذه الحالة لوجود كوبونات مرتبطة بها'], 422);
            }
            return redirect()->back()
                ->with('error', 'لا يمكن حذف هذه الحالة لوجود كوبونات مرتبطة بها');
        }

        try {
            DB::beginTransaction();

            $recordId = $status->id;
            $oldValues = $status->toArray();
            $status->delete();

            DB::afterCommit(function () use ($recordId, $oldValues) {
                $this->auditService->log(
                    auth()->id(),
                    'delete',
                    'coupon_statuses',
                    $recordId,
                    $oldValues,
                    null
                );

                cache()->forget('coupon-statuses-list');
            });

            DB::commit();

            return redirect()->route('admin.lookups.coupon-statuses.index')
                ->with('success', 'تم حذف حالة الكوبون بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('CouponStatus destroy failed', [
                'error' => $e->getMessage(),
                'id'    => $status->id,
            ]);
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'تعذر إتمام عملية الحذف. يرجى المحاولة لاحقاً.'], 422);
            }
            return back()
                ->with('error', 'تعذر إتمام عملية الحذف. يرجى المحاولة لاحقاً.');
        }
    }
    */
}