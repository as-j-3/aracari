<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Vehicle\StoreVehicleTypeRequest;
use App\Http\Requests\Web\Vehicle\UpdateVehicleTypeRequest;
use App\Http\Resources\Admin\VehicleTypeResource;
use App\Models\VehicleType;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class VehicleTypeController extends Controller
{
    protected AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
        $this->middleware('require.admin');
    }

    public function index(Request $request): JsonResponse
    {
        /*
        // الكود السابق (Web View):
        $vehicleTypes = VehicleType::withCount('vehicles')->paginate(15);
        return view('admin.vehicle-types.index', compact('vehicleTypes'));
        */

        // كود الـ API:
        $query = VehicleType::withCount('vehicles');

        if ($search = $request->get('search')) {
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search);
            $query->where('type_name', 'like', "%{$escaped}%");
        }

        $perPage = (int) $request->get('per_page', 15);
        $vehicleTypes = $query->latest('id')->paginate($perPage);

        // حساب الإحصائيات الثلاث (المطابقة لبطاقات الواجهة)
        $allItems = $vehicleTypes->getCollection();
        $totalTypes = $vehicleTypes->total();
        $usedTypes = $allItems->filter(fn ($v) => ($v->vehicles_count ?? 0) > 0)->count();
        $totalVehicles = (int) $allItems->sum('vehicles_count');

        $statistics = [
            'total_types'    => $totalTypes,
            'used_types'     => $usedTypes,
            'total_vehicles' => $totalVehicles,
        ];

        return $this->success([
            'statistics'    => $statistics,
            'vehicle_types' => VehicleTypeResource::collection($vehicleTypes)->response()->getData(true),
        ], 'تم جلب أنواع المركبات بنجاح');
    }

    /*
    * دالة create خاصة بعرض صفحة الـ HTML (تم تركها معلقة)
    */
    public function create(): View
    {
        return view('admin.vehicle-types.create');
    }

    public function store(StoreVehicleTypeRequest $request): JsonResponse
    {
        /*
        // الكود السابق (Web Redirect):
        try {
            DB::beginTransaction();
            $vehicleType = VehicleType::create($request->validated());
            $vehicleTypeId = $vehicleType->id;
            $vehicleTypeData = $vehicleType->toArray();
            DB::afterCommit(function () use ($vehicleTypeId, $vehicleTypeData) {
                $this->auditService->log(auth()->id(), 'create', 'vehicle_types', $vehicleTypeId, null, $vehicleTypeData);
            });
            DB::commit();

            return redirect()->route('admin.vehicle_types.index')->with('success', 'تم إضافة نوع المركبة بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('VehicleType store failed: ' . $e->getMessage());
            return back()->withInput()->with('error', 'حدث خطأ أثناء إضافة نوع المركبة. يرجى المحاولة مرة أخرى.');
        }
        */

        // كود الـ API:
        try {
            DB::beginTransaction();
            $vehicleType = VehicleType::create($request->validated());
            $vehicleTypeId = $vehicleType->id;
            $vehicleTypeData = $vehicleType->toArray();

            DB::afterCommit(function () use ($vehicleTypeId, $vehicleTypeData) {
                $this->auditService->log(auth()->id(), 'create', 'vehicle_types', $vehicleTypeId, null, $vehicleTypeData);
                cache()->forget('vehicle-types-list');
            });

            DB::commit();

            return $this->success(new VehicleTypeResource($vehicleType), 'تم إضافة نوع المركبة بنجاح', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('VehicleType store failed: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return $this->error('حدث خطأ أثناء إضافة نوع المركبة. يرجى المحاولة مرة أخرى.', null, 500);
        }
    }

    public function show(VehicleType $vehicleType): JsonResponse
    {
        $vehicleType->loadCount('vehicles');
        return $this->success(new VehicleTypeResource($vehicleType), 'تم جلب تفاصيل نوع المركبة بنجاح');
    }

    /*
    * دالة edit خاصة بعرض صفحة الـ HTML (تم تركها معلقة)
    */
    public function edit(VehicleType $vehicleType): View
    {
        return view('admin.vehicle-types.edit', compact('vehicleType'));
    }

    public function update(UpdateVehicleTypeRequest $request, VehicleType $vehicleType): JsonResponse
    {
        /*
        // الكود السابق (Web Redirect):
        try {
            DB::beginTransaction();
            $oldData = $vehicleType->toArray();
            $vehicleType->update($request->validated());
            $newData = $vehicleType->fresh()->toArray();

            $changes = array_diff_assoc($newData, $oldData);
            if (!empty($changes)) {
                $vehicleTypeId = $vehicleType->id;
                $oldSubset = array_intersect_key($oldData, $changes);
                DB::afterCommit(function () use ($vehicleTypeId, $oldSubset, $changes) {
                    $this->auditService->log(
                        auth()->id(),
                        'update',
                        'vehicle_types',
                        $vehicleTypeId,
                        $oldSubset,
                        $changes
                    );
                });
            }
            DB::commit();

            return redirect()->route('admin.vehicle_types.index')->with('success', 'تم تحديث نوع المركبة بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('VehicleType update failed: ' . $e->getMessage());
            return back()->withInput()->with('error', 'حدث خطأ أثناء تحديث نوع المركبة. يرجى المحاولة مرة أخرى.');
        }
        */

        // كود الـ API:
        try {
            DB::beginTransaction();
            $oldData = $vehicleType->toArray();
            $vehicleType->update($request->validated());
            $newData = $vehicleType->fresh()->toArray();

            $changes = array_diff_assoc($newData, $oldData);
            if (!empty($changes)) {
                $vehicleTypeId = $vehicleType->id;
                $oldSubset = array_intersect_key($oldData, $changes);
                DB::afterCommit(function () use ($vehicleTypeId, $oldSubset, $changes) {
                    $this->auditService->log(
                        auth()->id(),
                        'update',
                        'vehicle_types',
                        $vehicleTypeId,
                        $oldSubset,
                        $changes
                    );
                    cache()->forget('vehicle-types-list');
                });
            }

            DB::commit();

            $vehicleType->loadCount('vehicles');
            return $this->success(new VehicleTypeResource($vehicleType), 'تم تحديث نوع المركبة بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('VehicleType update failed: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return $this->error('حدث خطأ أثناء تحديث نوع المركبة. يرجى المحاولة مرة أخرى.', null, 500);
        }
    }

    public function destroy(VehicleType $vehicleType): JsonResponse
    {
        /*
        // الكود السابق (Web Redirect):
        if ($vehicleType->vehicles()->exists()) {
            return redirect()->route('admin.vehicle_types.index')
                ->with('error', 'لا يمكن حذف نوع المركبة لأنه مرتبط بمركبات. قم بتعديل المركبات المرتبطة أولاً.');
        }

        try {
            DB::beginTransaction();
            $oldData = $vehicleType->toArray();
            $vehicleTypeId = $vehicleType->id;
            $vehicleType->delete();

            DB::afterCommit(function () use ($vehicleTypeId, $oldData) {
                $this->auditService->log(auth()->id(), 'delete', 'vehicle_types', $vehicleTypeId, $oldData, null);
            });
            DB::commit();

            return redirect()->route('admin.vehicle_types.index')->with('success', 'تم حذف نوع المركبة بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('VehicleType destroy failed: ' . $e->getMessage());
            return back()->with('error', 'تعذر إتمام عملية الحذف. يرجى المحاولة لاحقاً.');
        }
        */

        // كود الـ API:
        if ($vehicleType->vehicles()->exists()) {
            return $this->error('لا يمكن حذف نوع المركبة لأنه مرتبط بمركبات. قم بتعديل المركبات المرتبطة أولاً.', null, 422);
        }

        try {
            DB::beginTransaction();
            $oldData = $vehicleType->toArray();
            $vehicleTypeId = $vehicleType->id;
            $vehicleType->delete();

            DB::afterCommit(function () use ($vehicleTypeId, $oldData) {
                $this->auditService->log(auth()->id(), 'delete', 'vehicle_types', $vehicleTypeId, $oldData, null);
                cache()->forget('vehicle-types-list');
            });

            DB::commit();

            return $this->success(null, 'تم حذف نوع المركبة بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('VehicleType destroy failed: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return $this->error('تعذر إتمام عملية الحذف. يرجى المحاولة لاحقاً.', null, 500);
        }
    }
}
