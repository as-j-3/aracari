<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LevelTrip;
use App\Http\Requests\Web\Trip\StoreTripLevelRequest;
use App\Http\Requests\Web\Trip\UpdateTripLevelRequest;
use App\Http\Requests\Web\Lookup\IndexLookupRequest;
use App\Services\AuditService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Builder;

class TripLevelController extends Controller
{
    use ApiResponse;

    protected AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
        $this->middleware('require.admin');
    }

    /*
    |--------------------------------------------------------------------------
    | دوال الـ API الجديدة (JSON Responses)
    |--------------------------------------------------------------------------
    */

    /**
     * عرض قائمة مستويات الرحلات مع إمكانية البحث والترقيم
     * GET /api/admin/lookups/trip-levels
     */
    public function index(IndexLookupRequest $request): JsonResponse
    {
        $query = LevelTrip::query();

        if ($search = $request->get('search')) {
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search);
            $query->where(fn (Builder $q) => $q->where('level_name', 'like', "%{$escaped}%"));
        }

        $levels = $query->latest('created_at')
            ->paginate($request->get('per_page', 15))
            ->withQueryString();

        return $this->success($levels, 'تم استرجاع مستويات الرحلات بنجاح');
    }

    /**
     * إضافة مستوى رحلة جديد
     * POST /api/admin/lookups/trip-levels
     */
    public function store(StoreTripLevelRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $level = LevelTrip::create($request->validated());

            DB::afterCommit(function () use ($level) {
                $this->auditService->log(
                    auth()->id(),
                    'create',
                    'level_trips',
                    $level->id,
                    null,
                    $level->fresh()->toArray()
                );

                cache()->forget('trip-levels-list');
            });

            DB::commit();

            return $this->success($level->fresh(), 'تم إضافة مستوى الرحلة بنجاح', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TripLevel store failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error('حدث خطأ أثناء إضافة المستوى. يرجى المحاولة مرة أخرى.', null, 500);
        }
    }

    /**
     * عرض تفاصيل مستوى رحلة محدد
     * GET /api/admin/lookups/trip-levels/{id}
     */


    /**
     * تحديث مستوى رحلة
     * PUT/POST /api/admin/lookups/trip-levels/{id}
     */
    public function update(UpdateTripLevelRequest $request, int $id): JsonResponse
    {
        $level = LevelTrip::find($id);

        if (!$level) {
            return $this->error('مستوى الرحلة غير موجود', null, 404);
        }

        try {
            DB::beginTransaction();

            $oldValues = $level->toArray();
            $level->update($request->validated());

            DB::afterCommit(function () use ($level, $oldValues) {
                $this->auditService->log(
                    auth()->id(),
                    'update',
                    'level_trips',
                    $level->id,
                    $oldValues,
                    $level->fresh()->toArray()
                );

                cache()->forget('trip-levels-list');
            });

            DB::commit();

            return $this->success($level->fresh(), 'تم تحديث مستوى الرحلة بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TripLevel update failed', [
                'error' => $e->getMessage(),
                'id'    => $level->id,
            ]);

            return $this->error('حدث خطأ أثناء تحديث المستوى. يرجى المحاولة مرة أخرى.', null, 500);
        }
    }

    /**
     * حذف مستوى رحلة
     * DELETE /api/admin/lookups/trip-levels/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $level = LevelTrip::find($id);

        if (!$level) {
            return $this->error('مستوى الرحلة غير موجود', null, 404);
        }

        if ($level->trips()->exists()) {
            return $this->error('لا يمكن حذف هذا المستوى لوجود رحلات مرتبطة به', null, 422);
        }

        try {
            DB::beginTransaction();

            $recordId = $level->id;
            $oldValues = $level->toArray();
            $level->delete();

            DB::afterCommit(function () use ($recordId, $oldValues) {
                $this->auditService->log(
                    auth()->id(),
                    'delete',
                    'level_trips',
                    $recordId,
                    $oldValues,
                    null
                );

                cache()->forget('trip-levels-list');
            });

            DB::commit();

            return $this->success(null, 'تم حذف مستوى الرحلة بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TripLevel destroy failed', [
                'error' => $e->getMessage(),
                'id'    => $level->id,
            ]);

            return $this->error('تعذر إتمام عملية الحذف. يرجى المحاولة لاحقاً.', null, 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | الكود السابق محفوظ كما هو معلّق (Web Views Flow)
    |--------------------------------------------------------------------------
    */
    /*
    public function index(IndexLookupRequest $request): View
    {
        $query = LevelTrip::query();

        if ($search = $request->get('search')) {
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search);
            $query->where(fn (Builder $q) => $q->where('level_name', 'like', "%{$escaped}%"));
        }

        $levels = $query->latest('created_at')
            ->paginate($request->get('per_page', 15))
            ->withQueryString();

        return view('admin.trip-levels.index', compact('levels'));
    }

    public function create(): View
    {
        return view('admin.trip-levels.create');
    }

    public function store(StoreTripLevelRequest $request): RedirectResponse|JsonResponse
    {
        try {
            DB::beginTransaction();

            $level = LevelTrip::create($request->validated());

            DB::afterCommit(function () use ($level) {
                $this->auditService->log(
                    auth()->id(),
                    'create',
                    'level_trips',
                    $level->id,
                    null,
                    $level->fresh()->toArray()
                );

                cache()->forget('trip-levels-list');
            });

            DB::commit();

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'تم إضافة مستوى الرحلة بنجاح']);
            }

            return redirect()->route('admin.lookups.trip-levels.index')
                ->with('success', 'تم إضافة مستوى الرحلة بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TripLevel store failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'حدث خطأ أثناء إضافة المستوى. يرجى المحاولة مرة أخرى.'], 422);
            }

            return back()->withInput()
                ->with('error', 'حدث خطأ أثناء إضافة المستوى. يرجى المحاولة مرة أخرى.');
        }
    }

    public function edit(int $id): View
    {
        $level = LevelTrip::findOrFail($id);
        return view('admin.trip-levels.edit', compact('level'));
    }

    public function update(UpdateTripLevelRequest $request, int $id): RedirectResponse|JsonResponse
    {
        $level = LevelTrip::findOrFail($id);

        try {
            DB::beginTransaction();

            $oldValues = $level->toArray();
            $level->update($request->validated());

            DB::afterCommit(function () use ($level, $oldValues) {
                $this->auditService->log(
                    auth()->id(),
                    'update',
                    'level_trips',
                    $level->id,
                    $oldValues,
                    $level->fresh()->toArray()
                );

                cache()->forget('trip-levels-list');
            });

            DB::commit();

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'تم تحديث مستوى الرحلة بنجاح']);
            }

            return redirect()->route('admin.lookups.trip-levels.index')
                ->with('success', 'تم تحديث مستوى الرحلة بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TripLevel update failed', [
                'error' => $e->getMessage(),
                'id'    => $level->id,
            ]);

            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'حدث خطأ أثناء تحديث المستوى. يرجى المحاولة مرة أخرى.'], 422);
            }

            return back()->withInput()
                ->with('error', 'حدث خطأ أثناء تحديث المستوى. يرجى المحاولة مرة أخرى.');
        }
    }

    public function destroy(Request $request, int $id): RedirectResponse|JsonResponse
    {
        $level = LevelTrip::findOrFail($id);

        if ($level->trips()->exists()) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'لا يمكن حذف هذا المستوى لوجود رحلات مرتبطة به'], 422);
            }
            return redirect()->back()
                ->with('error', 'لا يمكن حذف هذا المستوى لوجود رحلات مرتبطة به');
        }

        try {
            DB::beginTransaction();

            $recordId = $level->id;
            $oldValues = $level->toArray();
            $level->delete();

            DB::afterCommit(function () use ($recordId, $oldValues) {
                $this->auditService->log(
                    auth()->id(),
                    'delete',
                    'level_trips',
                    $recordId,
                    $oldValues,
                    null
                );

                cache()->forget('trip-levels-list');
            });

            DB::commit();

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'تم حذف مستوى الرحلة بنجاح']);
            }

            return redirect()->route('admin.lookups.trip-levels.index')
                ->with('success', 'تم حذف مستوى الرحلة بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TripLevel destroy failed', [
                'error' => $e->getMessage(),
                'id'    => $level->id,
            ]);

            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'تعذر إتمام عملية الحذف. يرجى المحاولة لاحقاً.'], 422);
            }

            return back()
                ->with('error', 'تعذر إتمام عملية الحذف. يرجى المحاولة لاحقاً.');
        }
    }
    */
}
