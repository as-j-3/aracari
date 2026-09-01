<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\City\StoreCityRequest;
use App\Http\Requests\Web\City\UpdateCityRequest;
use App\Models\City;
use App\Models\Country;
use App\Services\AuditService;
use App\Services\FileUploadService;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log; 

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
}
