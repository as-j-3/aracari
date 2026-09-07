<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\ProviderService\CreateTypeOfServiceRequest;
use App\Http\Requests\Web\ProviderService\UpdateTypeOfServiceRequest;
use App\Models\TypeOfService;
use App\Services\AuditService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class TypeOfServiceController extends Controller
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
     * عرض قائمة أنواع الخدمات التشغيلية
     * GET /api/admin/service-types
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $search = $request->get('search');

        $services = TypeOfService::query()
            ->when($search, function ($query) use ($search) {
                $query->where('type_name', 'like', "%{$search}%");
            })
            ->orderBy('type_name')
            ->paginate($perPage);

        return $this->success($services, 'تم استرجاع أنواع الخدمات بنجاح');
    }

    /**
     * إنشاء نوع خدمة تشغيلية جديد
     * POST /api/admin/service-types
     */
    public function store(CreateTypeOfServiceRequest $request): JsonResponse
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

            return $this->success($serviceType, 'تم إضافة الخدمة التشغيلية بنجاح', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TypeOfService store failed: '.$e->getMessage());

            return $this->error('حدث خطأ أثناء إضافة الخدمة. يرجى المحاولة مرة أخرى.', null, 500);
        }
    }

    /**
     * عرض تفاصيل نوع خدمة تشغيلية محدد
     * GET /api/admin/service-types/{serviceType}
     */
    public function show(TypeOfService $serviceType): JsonResponse
    {
        return $this->success($serviceType, 'تم استرجاع تفاصيل الخدمة التشغيلية بنجاح');
    }

    /**
     * تحديث بيانات نوع خدمة تشغيلية
     * PUT/PATCH /api/admin/service-types/{serviceType}
     */
    public function update(UpdateTypeOfServiceRequest $request, TypeOfService $serviceType): JsonResponse
    {
        DB::beginTransaction();
        try {
            $oldData = $serviceType->toArray();
            $data = $request->validated();

            $serviceType->update($data);
            $newData = $serviceType->fresh()->toArray();

            $changes = array_diff_assoc($newData, $oldData);
            if (! empty($changes)) {
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

            return $this->success($serviceType->fresh(), 'تم تحديث الخدمة التشغيلية بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TypeOfService update failed: '.$e->getMessage());

            return $this->error('حدث خطأ أثناء تحديث الخدمة. يرجى المحاولة مرة أخرى.', null, 500);
        }
    }

    /**
     * حذف نوع خدمة تشغيلية (مع التحقق من عدم وجود مزودين مرتبطين)
     * DELETE /api/admin/service-types/{serviceType}
     */
    public function destroy(TypeOfService $serviceType): JsonResponse
    {
        if ($serviceType->providers()->exists()) {
            return $this->error('لا يمكن حذف الخدمة التشغيلية لأنها مستخدمة من قبل مزودي الخدمة', null, 422);
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

            return $this->success(null, 'تم حذف الخدمة التشغيلية بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TypeOfService destroy failed: '.$e->getMessage());

            return $this->error('تعذر إتمام عملية الحذف. يرجى المحاولة لاحقاً.', null, 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | الكود السابق محفوظ كما هو معلّق (Web Views Flow)
    |--------------------------------------------------------------------------
    */
    /*
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
    */
}
