<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\City\StoreCityRequest;
use App\Http\Requests\Web\City\UpdateCityRequest;
use App\Models\City;
use App\Models\Country;
use App\Services\AuditService;
use App\Services\FileUploadService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;


class CityController extends Controller
{
    protected AuditService $auditService;

    protected FileUploadService $fileUploadService;

    public function __construct(AuditService $auditService, FileUploadService $fileUploadService)
    {
        $this->auditService = $auditService;
        $this->fileUploadService = $fileUploadService;
        $this->middleware('require.admin');
    }

    public function index(Request $request): JsonResponse
    {
        $query = City::query()
            ->with('country');

        if ($search = $request->get('search')) {
            $escaped = str_replace(
                ['\\', '%', '_'],
                ['\\\\', '\\%', '\\_'],
                $search
            );

            $query->where(function (Builder $q) use ($escaped) {
                $q->where('name', 'like', "%{$escaped}%");
            });
        }


        $cities = $query
            ->latest('created_at')
            ->paginate($request->get('per_page', 15))
            ->withQueryString();

        return $this->success(
            $cities,
            'تم استرجاع المدن بنجاح'
        );
    }

    /**
     * إضافة مدينة
     */
    public function store(StoreCityRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();

            DB::beginTransaction();

            $city = City::create($data);


            if ($request->hasFile('image')) {
                $result = $this->fileUploadService->uploadImage(
                    $request->file('image'),
                    'cities/'.$city->id
                );

                if ($result['success']) {
                    $city->update([
                        'image' => $result['file_path'],
                    ]);
                }
            }

            $cityId = $city->id;
            $cityData = $city->fresh()
                ->load('country')
                ->toArray();

            DB::commit();


            $this->auditService->log(
                auth()->id(),
                'create',
                'cities',
                $cityId,
                null,
                $cityData
            );

            return $this->success(
                $city->fresh()->load('country'),
                'تم إضافة المدينة بنجاح'
            );

        } catch (\Exception $e) {

            DB::rollBack();

            Log::error('City store failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(
                'حدث خطأ أثناء إضافة المدينة. يرجى المحاولة مرة أخرى.',
                null,
                422
            );
        }
    }

    /**
     * تعديل مدينة
     */
    public function update(
        UpdateCityRequest $request,
        City $city
    ): JsonResponse {
        try {
            $oldData = $city->toArray();
            $data = $request->validated();
            $oldImage = $city->image;

            DB::beginTransaction();

            /*
            |--------------------------------------------------------------------------
            | رفع الصورة الجديدة
            |--------------------------------------------------------------------------
            */

            if ($request->hasFile('image')) {
                $result = $this->fileUploadService->uploadImage(
                    $request->file('image'),
                    'cities/'.$city->id
                );

                if ($result['success']) {
                    $data['image'] = $result['file_path'];
                }
            }

            $city->update($data);

            $newData = $city->fresh()->toArray();

            $changes = array_diff_assoc(
                $newData,
                $oldData
            );

            DB::commit();

            /*
            |--------------------------------------------------------------------------
            | حذف الصورة القديمة بعد نجاح العملية
            |--------------------------------------------------------------------------
            */

            if ($oldImage && isset($changes['image'])) {
                $this->fileUploadService->deleteFile($oldImage);
            }

            /*
            |--------------------------------------------------------------------------
            | Audit
            |--------------------------------------------------------------------------
            */

            if (! empty($changes)) {
                $this->auditService->log(
                    auth()->id(),
                    'update',
                    'cities',
                    $city->id,
                    array_intersect_key($oldData, $changes),
                    $changes
                );
            }

            return $this->success(
                $city->fresh()->load('country'),
                'تم تحديث المدينة بنجاح'
            );

        } catch (\Exception $e) {

            DB::rollBack();

            Log::error('City update failed', [
                'error' => $e->getMessage(),
                'id' => $city->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(
                'حدث خطأ أثناء تحديث المدينة. يرجى المحاولة مرة أخرى.',
                null,
                422
            );
        }
    }

    /**
     * حذف مدينة
     */
    public function destroy(City $city): JsonResponse
    {
        /*
        |--------------------------------------------------------------------------
        | منع حذف مدينة مرتبطة برحلات
        |--------------------------------------------------------------------------
        */

        if (
            $city->departingTrips()->exists() ||
            $city->arrivingTrips()->exists()
        ) {
            return $this->error(
                'لا يمكن حذف مدينة مرتبطة برحلات',
                null,
                422
            );
        }

        try {
            $oldData = $city->toArray();
            $image = $city->image;
            $cityId = $city->id;

            DB::beginTransaction();

            $city->delete();

            DB::commit();

            /*
            |--------------------------------------------------------------------------
            | حذف الصورة
            |--------------------------------------------------------------------------
            */

            if ($image) {
                $this->fileUploadService->deleteFile($image);
            }

            /*
            |--------------------------------------------------------------------------
            | Audit
            |--------------------------------------------------------------------------
            */

            $this->auditService->log(
                auth()->id(),
                'delete',
                'cities',
                $cityId,
                $oldData,
                null
            );

            return $this->success(
                null,
                'تم حذف المدينة بنجاح'
            );

        } catch (\Exception $e) {

            DB::rollBack();

            Log::error('City destroy failed', [
                'error' => $e->getMessage(),
                'id' => $cityId,
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(
                'تعذر إتمام عملية الحذف. يرجى المحاولة لاحقاً.',
                null,
                422
            );
        }
    }

    /*
    public function index(): View
    {
        $cities = City::with('country')->paginate(15);
        return view('admin.cities.index', compact('cities'));
    }

    public function create(): View
    {
        $countries = Country::orderBy('name')->pluck('name', 'id');
        return view('admin.cities.create', compact('countries'));
    }

    public function store(StoreCityRequest $request): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        try {
            $data = $request->validated();

            DB::beginTransaction();
            $city = City::create($data);

            if ($request->hasFile('image')) {
                $result = $this->fileUploadService->uploadImage($request->file('image'), 'cities/' . $city->id);
                if ($result['success']) {
                    $city->update(['image' => $result['file_path']]);
                }
            }

            $cityId = $city->id;
            $cityData = $city->fresh()->toArray();
            DB::afterCommit(function () use ($cityId, $cityData) {
                $this->auditService->log(auth()->id(), 'create', 'cities', $cityId, null, $cityData);
            });

            DB::commit();

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'تم إضافة المدينة بنجاح']);
            }

            return redirect()->route('admin.cities.index')->with('success', 'تم إضافة المدينة بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('City store failed: ' . $e->getMessage());
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'حدث خطأ أثناء إضافة المدينة. يرجى المحاولة مرة أخرى.'], 422);
            }
            return back()->withInput()->with('error', 'حدث خطأ أثناء إضافة المدينة. يرجى المحاولة مرة أخرى.');
        }
    }

    public function edit(City $city): View
    {
        $countries = Country::orderBy('name')->pluck('name', 'id');
        return view('admin.cities.edit', compact('city', 'countries'));
    }

    public function update(UpdateCityRequest $request, City $city): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        try {
            $oldData = $city->toArray();
            $data = $request->validated();
            $oldImage = $city->image;

            DB::beginTransaction();
            if ($request->hasFile('image')) {
                $result = $this->fileUploadService->uploadImage($request->file('image'), 'cities/' . $city->id);
                if ($result['success']) {
                    $data['image'] = $result['file_path'];
                }
            }

            $city->update($data);
            $newData = $city->fresh()->toArray();

            $changes = array_diff_assoc($newData, $oldData);

            DB::afterCommit(function () use ($oldImage, $city, $oldData, $changes) {
                if ($oldImage && isset($changes['image'])) {
                    $this->fileUploadService->deleteFile($oldImage);
                }
                if (!empty($changes)) {
                    $this->auditService->log(
                        auth()->id(),
                        'update',
                        'cities',
                        $city->id,
                        array_intersect_key($oldData, $changes),
                        $changes
                    );
                }
            });

            DB::commit();

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'تم تحديث المدينة بنجاح']);
            }

            return redirect()->route('admin.cities.index')->with('success', 'تم تحديث المدينة بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('City update failed: ' . $e->getMessage());
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'حدث خطأ أثناء تحديث المدينة. يرجى المحاولة مرة أخرى.'], 422);
            }
            return back()->withInput()->with('error', 'حدث خطأ أثناء تحديث المدينة. يرجى المحاولة مرة أخرى.');
        }
    }

    public function destroy(Request $request, City $city): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        if ($city->departingTrips()->exists() || $city->arrivingTrips()->exists()) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'لا يمكن حذف مدينة مرتبطة برحلات'], 422);
            }
            return redirect()->route('admin.cities.index')->with('error', 'لا يمكن حذف مدينة مرتبطة برحلات');
        }

        try {
            $oldData = $city->toArray();
            $image = $city->image;
            $cityId = $city->id;

            DB::beginTransaction();
            $city->delete();

            DB::afterCommit(function () use ($image, $cityId, $oldData) {
                if ($image) {
                    $this->fileUploadService->deleteFile($image);
                }
                $this->auditService->log(auth()->id(), 'delete', 'cities', $cityId, $oldData, null);
            });

            DB::commit();

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'تم حذف المدينة بنجاح']);
            }

            return redirect()->route('admin.cities.index')->with('success', 'تم حذف المدينة بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('City destroy failed: ' . $e->getMessage());
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'تعذر إتمام عملية الحذف. يرجى المحاولة لاحقاً.'], 422);
            }
            return back()->with('error', 'تعذر إتمام عملية الحذف. يرجى المحاولة لاحقاً.');
        }
    }
        */
}
