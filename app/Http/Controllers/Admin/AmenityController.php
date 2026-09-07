<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Amenity\StoreAmenityRequest;
use App\Http\Requests\Web\Amenity\UpdateAmenityRequest;
use App\Models\Amenity;
use App\Services\AuditService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AmenityController extends Controller
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
     * عرض قائمة المرافق والخدمات (Amenities) مع إمكانية البحث والترقيم
     * GET /api/admin/amenities
     */
    public function index(Request $request): JsonResponse
    {
        $query = Amenity::query();

        if ($search = $request->get('search')) {
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search);
            $query->where('name', 'like', "%{$escaped}%");
        }

        $amenities = $query->latest('created_at')
            ->paginate($request->get('per_page', 15))
            ->withQueryString();

        return $this->success($amenities, 'تم استرجاع الخدمات والمرافق بنجاح');
    }

    /**
     * إضافة مرفق / خدمة جديدة
     * POST /api/admin/amenities
     */
    public function store(StoreAmenityRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $amenity = Amenity::create($request->validated());

            $this->auditService->log(
                auth()->id(),
                'create',
                'amenities',
                $amenity->id,
                null,
                $amenity->toArray()
            );

            DB::commit();

            return $this->success($amenity->fresh(), 'تم إضافة الخدمة بنجاح', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Amenity store failed: ' . $e->getMessage());

            return $this->error('حدث خطأ أثناء إضافة الخدمة. يرجى المحاولة مرة أخرى.', null, 500);
        }
    }

    /**
     * تحديث مرفق / خدمة
     * PUT/POST /api/admin/amenities/{amenity:uuid}
     */
    public function update(UpdateAmenityRequest $request, Amenity $amenity): JsonResponse
    {
        DB::beginTransaction();
        try {
            $oldData = $amenity->toArray();
            $amenity->update($request->validated());
            $newData = $amenity->fresh()->toArray();

            $changes = array_diff_assoc($newData, $oldData);
            if (!empty($changes)) {
                $this->auditService->log(
                    auth()->id(),
                    'update',
                    'amenities',
                    $amenity->id,
                    array_intersect_key($oldData, $changes),
                    $changes
                );
            }

            DB::commit();

            return $this->success($amenity->fresh(), 'تم تحديث الخدمة بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Amenity update failed: ' . $e->getMessage());

            return $this->error('حدث خطأ أثناء تحديث الخدمة. يرجى المحاولة مرة أخرى.', null, 500);
        }
    }

    /**
     * حذف مرفق / خدمة (مع التحقق من عدم ارتباطها برحلات)
     * DELETE /api/admin/amenities/{amenity:uuid}
     */
    public function destroy(Amenity $amenity): JsonResponse
    {
        if ($amenity->tripAmenities()->exists()) {
            return $this->error('لا يمكن حذف خدمة مرتبطة برحلات', null, 422);
        }

        DB::beginTransaction();
        try {
            $oldData = $amenity->toArray();

            $this->auditService->log(
                auth()->id(),
                'delete',
                'amenities',
                $amenity->id,
                $oldData,
                null
            );

            $amenity->delete();

            DB::commit();

            return $this->success(null, 'تم حذف الخدمة بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Amenity destroy failed: ' . $e->getMessage());

            return $this->error('تعذر إتمام عملية الحذف. يرجى المحاولة لاحقاً.', null, 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | الكود السابق محفوظ كما هو معلّق (Web Views Flow)
    |--------------------------------------------------------------------------
    */
    /*
    public function index(): View
    {
        $amenities = Amenity::paginate(15);
        return view('admin.amenities.index', compact('amenities'));
    }

    public function create(): View
    {
        return view('admin.amenities.create');
    }

    public function store(StoreAmenityRequest $request): RedirectResponse|JsonResponse
    {
        try {
            $amenity = Amenity::create($request->validated());

            $this->auditService->log(
                auth()->id(),
                'create',
                'amenities',
                $amenity->id,
                null,
                $amenity->toArray()
            );

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'تم إضافة الخدمة بنجاح']);
            }

            return redirect()->route('admin.amenities.index')
                ->with('success', 'تم إضافة الخدمة بنجاح');
        } catch (\Exception $e) {
            Log::error('Amenity store failed: ' . $e->getMessage());

            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'حدث خطأ أثناء إضافة الخدمة. يرجى المحاولة مرة أخرى.'], 422);
            }

            return back()->withInput()->with('error', 'حدث خطأ أثناء إضافة الخدمة. يرجى المحاولة مرة أخرى.');
        }
    }

    public function edit(Amenity $amenity): View
    {
        return view('admin.amenities.edit', compact('amenity'));
    }

    public function update(UpdateAmenityRequest $request, Amenity $amenity): RedirectResponse|JsonResponse
    {
        try {
            $oldData = $amenity->toArray();
            $amenity->update($request->validated());
            $newData = $amenity->fresh()->toArray();

            $changes = array_diff_assoc($newData, $oldData);
            if (!empty($changes)) {
                $this->auditService->log(
                    auth()->id(),
                    'update',
                    'amenities',
                    $amenity->id,
                    array_intersect_key($oldData, $changes),
                    $changes
                );
            }

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'تم تحديث الخدمة بنجاح']);
            }

            return redirect()->route('admin.amenities.index')
                ->with('success', 'تم تحديث الخدمة بنجاح');
        } catch (\Exception $e) {
            Log::error('Amenity update failed: ' . $e->getMessage());

            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'حدث خطأ أثناء تحديث الخدمة. يرجى المحاولة مرة أخرى.'], 422);
            }

            return back()->withInput()->with('error', 'حدث خطأ أثناء تحديث الخدمة. يرجى المحاولة مرة أخرى.');
        }
    }

    public function destroy(Request $request, Amenity $amenity): RedirectResponse|JsonResponse
    {
        if ($amenity->tripAmenities()->exists()) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'لا يمكن حذف خدمة مرتبطة برحلات'], 422);
            }
            return redirect()->route('admin.amenities.index')
                ->with('error', 'لا يمكن حذف خدمة مرتبطة برحلات');
        }

        try {
            $this->auditService->log(
                auth()->id(),
                'delete',
                'amenities',
                $amenity->id,
                $amenity->toArray(),
                null
            );

            $amenity->delete();

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'تم حذف الخدمة بنجاح']);
            }

            return redirect()->route('admin.amenities.index')
                ->with('success', 'تم حذف الخدمة بنجاح');
        } catch (\Exception $e) {
            Log::error('Amenity destroy failed: ' . $e->getMessage());

            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'تعذر إتمام عملية الحذف. يرجى المحاولة لاحقاً.'], 422);
            }

            return back()->with('error', 'تعذر إتمام عملية الحذف. يرجى المحاولة لاحقاً.');
        }
    }
    */
}
