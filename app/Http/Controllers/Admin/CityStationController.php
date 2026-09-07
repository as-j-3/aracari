<?php

namespace App\Http\Controllers\Admin;


use App\Http\Controllers\Controller;
use App\Http\Requests\Web\CityStation\StoreCityStationRequest;
use App\Http\Requests\Web\CityStation\UpdateCityStationRequest;
use App\Models\CityStation;
use App\Services\AuditService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Trip;

class CityStationController extends Controller
{
    public function __construct(
        private AuditService $auditService
    ) {
        $this->middleware('require.admin');
    }

    public function index(Request $request): JsonResponse
    {
        $query = CityStation::query()
            ->with('city');

        /*
        |--------------------------------------------------------------------------
        | البحث
        |--------------------------------------------------------------------------
        */

        if ($search = $request->get('search')) {
            $escaped = str_replace(
                ['\\', '%', '_'],
                ['\\\\', '\\%', '\\_'],
                $search
            );

            $query->where(function (Builder $q) use ($escaped) {
                $q->where('name', 'like', "%{$escaped}%")
                    ->orWhereHas('city', function (Builder $cityQuery) use ($escaped) {
                        $cityQuery->where('name', 'like', "%{$escaped}%");
                    });
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Pagination
        |--------------------------------------------------------------------------
        */

        $stations = $query
            ->orderBy('city_id')
            ->orderBy('name')
            ->paginate($request->get('per_page', 20))
            ->withQueryString();

        return $this->success(
            $stations,
            'تم استرجاع المحطات بنجاح'
        );
    }

    /**
     * إضافة محطة
     */
    public function store(StoreCityStationRequest $request): JsonResponse
    {
        try {
            $station = CityStation::create(
                $request->validated()
            );

            $this->auditService->log(
                auth()->id(),
                'create',
                'city_stations',
                $station->id,
                null,
                $station->fresh()->toArray()
            );

            return $this->success(
                $station->fresh()->load('city'),
                'تم إضافة المحطة بنجاح'
            );

        } catch (\Exception $e) {

            Log::error('CityStation store failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(
                'حدث خطأ أثناء إضافة المحطة. يرجى المحاولة مرة أخرى.',
                null,
                422
            );
        }
    }

    /**
     * عرض محطة محددة
     */
    public function show(CityStation $cityStation): JsonResponse
    {
        $cityStation->load('city');

        return $this->success(
            $cityStation,
            'تم استرجاع بيانات المحطة بنجاح'
        );
    }

    /**
     * تعديل محطة
     */
    public function update(
        UpdateCityStationRequest $request,
        CityStation $cityStation
    ): JsonResponse {
        try {
            $oldData = $cityStation->toArray();

            $cityStation->update(
                $request->validated()
            );

            $newData = $cityStation->fresh()->toArray();

            $changes = array_diff_assoc(
                $newData,
                $oldData
            );

            if (! empty($changes)) {
                $this->auditService->log(
                    auth()->id(),
                    'update',
                    'city_stations',
                    $cityStation->id,
                    array_intersect_key($oldData, $changes),
                    $changes
                );
            }

            return $this->success(
                $cityStation->fresh()->load('city'),
                'تم تحديث المحطة بنجاح'
            );

        } catch (\Exception $e) {

            Log::error('CityStation update failed', [
                'error' => $e->getMessage(),
                'id' => $cityStation->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(
                'حدث خطأ أثناء تحديث المحطة. يرجى المحاولة مرة أخرى.',
                null,
                422
            );
        }
    }

    /**
     * حذف محطة
     */
    public function destroy(CityStation $cityStation): JsonResponse
    {
        /*
        |--------------------------------------------------------------------------
        | منع الحذف إذا كانت المحطة مرتبطة برحلات
        |--------------------------------------------------------------------------
        */

        $hasTrips = Trip::query()
            ->where('pickup_location_id', $cityStation->id)
            ->orWhere('arrival_location_id', $cityStation->id)
            ->exists();

        if ($hasTrips) {
            return $this->error(
                'لا يمكن حذف المحطة لوجود رحلات مرتبطة بها',
                null,
                422
            );
        }

        try {
            $oldData = $cityStation->toArray();

            $cityStation->delete();

            $this->auditService->log(
                auth()->id(),
                'delete',
                'city_stations',
                $cityStation->id,
                $oldData,
                null
            );

            return $this->success(
                null,
                'تم حذف المحطة بنجاح'
            );

        } catch (\Exception $e) {

            Log::error('CityStation destroy failed', [
                'error' => $e->getMessage(),
                'id' => $cityStation->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(
                'تعذر إتمام عملية الحذف. يرجى المحاولة لاحقاً.',
                null,
                500
            );
        }
    }
    /*
        public function index(): View
        {
            $stations = CityStation::with('city')
                ->orderBy('city_id')
                ->orderBy('name')
                ->paginate(20);

            return view('admin.city-stations.index', compact('stations'));
        }

        public function create(): View
        {
            $cities = City::orderBy('name')->pluck('name', 'id');
            return view('admin.city-stations.create', compact('cities'));
        }

        public function store(StoreCityStationRequest $request): RedirectResponse
        {
            try {
                $station = CityStation::create($request->validated());

                $this->auditService->log(
                    auth()->id(),
                    'create',
                    'city_stations',
                    $station->id,
                    null,
                    $station->toArray()
                );

                return redirect()->route('admin.city_stations.index')
                    ->with('success', 'تم إضافة المحطة بنجاح');
            } catch (\Exception $e) {
                Log::error('CityStation store failed: ' . $e->getMessage());
                return back()->withInput()->with('error', 'حدث خطأ أثناء إضافة المحطة. يرجى المحاولة مرة أخرى.');
            }
        }

        public function show(CityStation $cityStation): View
        {
            $cityStation->load('city');
            return view('admin.city-stations.show', compact('cityStation'));
        }

        public function edit(CityStation $cityStation): View
        {
            $cities = City::orderBy('name')->pluck('name', 'id');
            return view('admin.city-stations.edit', compact('cityStation', 'cities'));
        }

        public function update(UpdateCityStationRequest $request, CityStation $cityStation): RedirectResponse
        {
            try {
                $oldData = $cityStation->toArray();
                $cityStation->update($request->validated());

                $changes = array_diff_assoc($cityStation->fresh()->toArray(), $oldData);
                if (!empty($changes)) {
                    $this->auditService->log(
                        auth()->id(),
                        'update',
                        'city_stations',
                        $cityStation->id,
                        array_intersect_key($oldData, $changes),
                        $changes
                    );
                }

                return redirect()->route('admin.city_stations.index')
                    ->with('success', 'تم تحديث المحطة بنجاح');
            } catch (\Exception $e) {
                Log::error('CityStation update failed: ' . $e->getMessage());
                return back()->withInput()->with('error', 'حدث خطأ أثناء تحديث المحطة. يرجى المحاولة مرة أخرى.');
            }
        }

        public function destroy(CityStation $cityStation): RedirectResponse|\Illuminate\Http\JsonResponse
        {
            $hasTrips = \App\Models\Trip::where('pickup_location_id', $cityStation->id)
                ->orWhere('arrival_location_id', $cityStation->id)
                ->exists();

            if ($hasTrips) {
                if (request()->ajax() || request()->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'لا يمكن حذف المحطة لوجود رحلات مرتبطة بها',
                    ]);
                }
                return redirect()->route('admin.city_stations.index')
                    ->with('error', 'لا يمكن حذف المحطة لوجود رحلات مرتبطة بها');
            }

            try {
                $oldData = $cityStation->toArray();
                $cityStation->delete();

                $this->auditService->log(
                    auth()->id(),
                    'delete',
                    'city_stations',
                    $cityStation->id,
                    $oldData,
                    null
                );

                if (request()->ajax() || request()->wantsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'تم حذف المحطة بنجاح',
                    ]);
                }
                return redirect()->route('admin.city_stations.index')
                    ->with('success', 'تم حذف المحطة بنجاح');
            } catch (\Exception $e) {
                Log::error('CityStation destroy failed: ' . $e->getMessage());
                if (request()->ajax() || request()->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'تعذر إتمام عملية الحذف. يرجى المحاولة لاحقاً.',
                    ], 500);
                }
                return back()->with('error', 'تعذر إتمام عملية الحذف. يرجى المحاولة لاحقاً.');
            }
        }
        */
}
