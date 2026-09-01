<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Vehicle\StoreVehicleTypeRequest;
use App\Http\Requests\Web\Vehicle\UpdateVehicleTypeRequest;
use App\Models\VehicleType;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class VehicleTypeController extends Controller
{
    protected AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
        $this->middleware('require.admin');
    }

 
    public function index(): View
    {
        $vehicleTypes = VehicleType::withCount('vehicles')->paginate(15);
        return view('admin.vehicle-types.index', compact('vehicleTypes'));
    }

    public function create(): View
    {
        return view('admin.vehicle-types.create');
    }


    public function store(StoreVehicleTypeRequest $request): RedirectResponse
    {
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
    }


    public function edit(VehicleType $vehicleType): View
    {
        return view('admin.vehicle-types.edit', compact('vehicleType'));
    }


    public function update(UpdateVehicleTypeRequest $request, VehicleType $vehicleType): RedirectResponse
    {
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
    }

    public function destroy(VehicleType $vehicleType): RedirectResponse
    {
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
    }
}
