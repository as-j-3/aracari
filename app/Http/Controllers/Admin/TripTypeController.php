<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TypeOfTrip;
use App\Http\Requests\Web\Trip\StoreTripTypeRequest;
use App\Http\Requests\Web\Trip\UpdateTripTypeRequest;
use App\Http\Requests\Web\Lookup\IndexLookupRequest;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Builder;

class TripTypeController extends Controller
{
    protected AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
        $this->middleware('require.admin');
    }

    public function index(IndexLookupRequest $request): View
    {
        $query = TypeOfTrip::query();

        if ($search = $request->get('search')) {
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search);
            $query->where(fn (Builder $q) => $q->where('type_name', 'like', "%{$escaped}%"));
        }

        $types = $query->latest('created_at')
            ->paginate($request->get('per_page', 15))
            ->withQueryString();

        return view('admin.trip-types.index', compact('types'));
    }

    public function create(): View
    {
        return view('admin.trip-types.create');
    }

    public function store(StoreTripTypeRequest $request): RedirectResponse|JsonResponse
    {
        try {
            DB::beginTransaction();

            $type = TypeOfTrip::create($request->validated());

            DB::afterCommit(function () use ($type) {
                $this->auditService->log(
                    auth()->id(),
                    'create',
                    'type_of_trips',
                    $type->id,
                    null,
                    $type->fresh()->toArray()
                );

                cache()->forget('trip-types-list');
            });

            DB::commit();

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'تم إضافة نوع الرحلة بنجاح']);
            }

            return redirect()->route('admin.lookups.trip-types.index')
                ->with('success', 'تم إضافة نوع الرحلة بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TripType store failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'حدث خطأ أثناء إضافة نوع الرحلة. يرجى المحاولة مرة أخرى.'], 422);
            }

            return back()->withInput()
                ->with('error', 'حدث خطأ أثناء إضافة نوع الرحلة. يرجى المحاولة مرة أخرى.');
        }
    }

    public function edit(int $id): View
    {
        $type = TypeOfTrip::findOrFail($id);
        return view('admin.trip-types.edit', compact('type'));
    }

    public function update(UpdateTripTypeRequest $request, int $id): RedirectResponse|JsonResponse
    {
        $type = TypeOfTrip::findOrFail($id);

        try {
            DB::beginTransaction();

            $oldValues = $type->toArray();
            $type->update($request->validated());

            DB::afterCommit(function () use ($type, $oldValues) {
                $this->auditService->log(
                    auth()->id(),
                    'update',
                    'type_of_trips',
                    $type->id,
                    $oldValues,
                    $type->fresh()->toArray()
                );

                cache()->forget('trip-types-list');
            });

            DB::commit();

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'تم تحديث نوع الرحلة بنجاح']);
            }

            return redirect()->route('admin.lookups.trip-types.index')
                ->with('success', 'تم تحديث نوع الرحلة بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TripType update failed', [
                'error' => $e->getMessage(),
                'id'    => $type->id,
            ]);

            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'حدث خطأ أثناء تحديث نوع الرحلة. يرجى المحاولة مرة أخرى.'], 422);
            }

            return back()->withInput()
                ->with('error', 'حدث خطأ أثناء تحديث نوع الرحلة. يرجى المحاولة مرة أخرى.');
        }
    }

    public function destroy(Request $request, int $id): RedirectResponse|JsonResponse
    {
        $type = TypeOfTrip::findOrFail($id);

        if ($type->trips()->exists()) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'لا يمكن حذف هذا النوع لوجود رحلات مرتبطة به'], 422);
            }
            return redirect()->back()
                ->with('error', 'لا يمكن حذف هذا النوع لوجود رحلات مرتبطة به');
        }

        try {
            DB::beginTransaction();

            $recordId = $type->id;
            $oldValues = $type->toArray();
            $type->delete();

            DB::afterCommit(function () use ($recordId, $oldValues) {
                $this->auditService->log(
                    auth()->id(),
                    'delete',
                    'type_of_trips',
                    $recordId,
                    $oldValues,
                    null
                );

                cache()->forget('trip-types-list');
            });

            DB::commit();

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'تم حذف نوع الرحلة بنجاح']);
            }

            return redirect()->route('admin.lookups.trip-types.index')
                ->with('success', 'تم حذف نوع الرحلة بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TripType destroy failed', [
                'error' => $e->getMessage(),
                'id'    => $type->id,
            ]);

            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'تعذر إتمام عملية الحذف. يرجى المحاولة لاحقاً.'], 422);
            }

            return back()
                ->with('error', 'تعذر إتمام عملية الحذف. يرجى المحاولة لاحقاً.');
        }
    }
}
