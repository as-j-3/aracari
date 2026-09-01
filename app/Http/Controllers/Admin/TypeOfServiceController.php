<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\ProviderService\CreateTypeOfServiceRequest;
use App\Http\Requests\Web\ProviderService\UpdateTypeOfServiceRequest;
use App\Models\TypeOfService;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log; 
use Illuminate\Support\Facades\DB;
class TypeOfServiceController extends Controller
{
    protected AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
        $this->middleware('require.admin');
    }

    public function index(Request $request): View
    {
        $perPage = $request->get('per_page', 15);
        $services = TypeOfService::orderBy('type_name')->paginate($perPage);
        
        return view('admin.service-types.index', compact('services'));
    }

    public function create(): View
    {
        return view('service_types.create');
    }

public function store(CreateTypeOfServiceRequest $request): RedirectResponse
{
    DB::beginTransaction();
    try {
        $data = $request->validated();
        $serviceType = TypeOfService::create($data);

        $this->auditService->log(
            auth()->id(),
            'create',
            'type_of_services',
            $serviceType->id,
            null,
            $serviceType->toArray()
        );

        DB::commit();
        return redirect()->route('admin.service-types.index')
            ->with('success', 'تم إضافة الخدمة التشغيلية بنجاح');
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('TypeOfService store failed: ' . $e->getMessage());
        return back()->withInput()->with('error', 'حدث خطأ أثناء إضافة الخدمة. يرجى المحاولة مرة أخرى.');
    }
}

    public function show(TypeOfService $serviceType): View
    {
        return view('service_types.show', compact('serviceType'));
    }

    public function edit(TypeOfService $serviceType): View
    {
        return view('service_types.edit', compact('serviceType'));
    }

    public function update(UpdateTypeOfServiceRequest $request, TypeOfService $serviceType): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $oldData = $serviceType->toArray();
            $data = $request->validated();

            $serviceType->update($data);
            $newData = $serviceType->fresh()->toArray();

            $changes = array_diff_assoc($newData, $oldData);
            if (!empty($changes)) {
                $this->auditService->log(
                    auth()->id(),
                    'update',
                    'type_of_services',
                    $serviceType->id,
                    array_intersect_key($oldData, $changes),
                    $changes
                );
            }

            DB::commit();
            return redirect()->route('admin.service-types.index')
                ->with('success', 'تم تحديث الخدمة التشغيلية بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TypeOfService update failed: ' . $e->getMessage());
            return back()->withInput()->with('error', 'حدث خطأ أثناء تحديث الخدمة. يرجى المحاولة مرة أخرى.');
        }
    }

    public function destroy(Request $request, TypeOfService $serviceType)
    {
        if ($serviceType->providers()->exists()) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'لا يمكن حذف الخدمة التشغيلية لأنها مستخدمة من قبل مزودي الخدمة'], 422);
            }
            return redirect()->back()
                ->with('error', 'لا يمكن حذف الخدمة التشغيلية لأنها مستخدمة من قبل مزودي الخدمة');
        }

        DB::beginTransaction();
        try {
            $oldData = $serviceType->toArray();

            $serviceType->delete();

            $this->auditService->log(
                auth()->id(),
                'delete',
                'type_of_services',
                $serviceType->id,
                $oldData,
                null
            );

            DB::commit();
            return redirect()->route('admin.service-types.index')
                ->with('success', 'تم حذف الخدمة التشغيلية بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TypeOfService destroy failed: ' . $e->getMessage());
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'تعذر إتمام عملية الحذف. يرجى المحاولة لاحقاً.'], 422);
            }
            return back()->with('error', 'تعذر إتمام عملية الحذف. يرجى المحاولة لاحقاً.');
        }
    }
}