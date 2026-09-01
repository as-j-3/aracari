<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CouponStatus;
use App\Http\Requests\Web\CouponStatus\StoreCouponStatusRequest;
use App\Http\Requests\Web\CouponStatus\UpdateCouponStatusRequest;
use App\Http\Requests\Web\Lookup\IndexLookupRequest;
use App\Services\AuditService;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Builder;

class CouponStatusController extends Controller
{
    protected AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
        $this->middleware('require.admin');
    }

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
}
