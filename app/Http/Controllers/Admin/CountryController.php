<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Country\IndexCountryRequest;
use App\Http\Requests\Web\Country\StoreCountryRequest;
use App\Http\Requests\Web\Country\UpdateCountryRequest;
use App\Models\Country;
use App\Services\AuditService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class CountryController extends Controller
{
    protected AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
        $this->middleware('require.admin');
    }

    public function index(IndexCountryRequest $request): JsonResponse
    {
        $query = Country::query();

        // البحث بالاسم أو رمز الدولة
        if ($search = $request->get('search')) {
            $escaped = str_replace(
                ['\\', '%', '_'],
                ['\\\\', '\\%', '\\_'],
                $search
            );

            $query->where(function (Builder $q) use ($escaped) {
                $q->where('name', 'like', "%{$escaped}%")
                    ->orWhere('iso_code', 'like', "%{$escaped}%");
            });
        }

        // فلترة حسب الحالة
        if ($request->has('is_active')) {
            $query->where(
                'is_active',
                $request->boolean('is_active')
            );
        }

        $countries = $query
            ->withCount('cities')
            ->orderBy('name')
            ->paginate($request->get('per_page', 15))
            ->withQueryString();

        return $this->success(
            $countries,
            'تم استرجاع الدول بنجاح'
        );
    }

    /**
     * إضافة دولة
     */
    public function store(StoreCountryRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $country = Country::create(
                $request->validated()
            );

            DB::commit();

            $this->auditService->log(
                auth()->id(),
                'create',
                'countries',
                $country->id,
                null,
                $country->fresh()->toArray()
            );

            cache()->forget('countries-list');

            return $this->success(
                $country->fresh(),
                'تم إضافة الدولة بنجاح'
            );

        } catch (\Exception $e) {

            DB::rollBack();

            Log::error('Country store failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(
                'حدث خطأ أثناء إضافة الدولة. يرجى المحاولة مرة أخرى.',
                null,
                422
            );
        }
    }

    /**
     * تعديل دولة
     */
    public function update(
        UpdateCountryRequest $request,
        Country $country
    ): JsonResponse {
        $oldData = $country->toArray();

        try {
            DB::beginTransaction();

            $country->update(
                $request->validated()
            );

            DB::commit();

            $newData = $country->fresh()->toArray();

            $this->auditService->log(
                auth()->id(),
                'update',
                'countries',
                $country->id,
                $oldData,
                $newData
            );

            cache()->forget('countries-list');

            return $this->success(
                $country->fresh(),
                'تم تحديث الدولة بنجاح'
            );

        } catch (\Exception $e) {

            DB::rollBack();

            Log::error('Country update failed', [
                'error' => $e->getMessage(),
                'id' => $country->id,
            ]);

            return $this->error(
                'حدث خطأ أثناء تحديث الدولة. يرجى المحاولة مرة أخرى.',
                null,
                422
            );
        }
    }

    /**
     * حذف دولة
     */
    public function destroy(
        Request $request,
        Country $country
    ): JsonResponse {
        // منع الحذف إذا كانت الدولة مرتبطة بمدن
        if ($country->cities()->exists()) {
            return $this->error(
                'لا يمكن حذف دولة مرتبطة بمدن',
                null,
                422
            );
        }

        $countryId = $country->id;
        $oldData = $country->toArray();

        try {
            DB::beginTransaction();

            $country->delete();

            DB::commit();

            $this->auditService->log(
                auth()->id(),
                'delete',
                'countries',
                $countryId,
                $oldData,
                null
            );

            cache()->forget('countries-list');

            return $this->success(
                null,
                'تم حذف الدولة بنجاح'
            );

        } catch (\Exception $e) {

            DB::rollBack();

            Log::error('Country destroy failed', [
                'error' => $e->getMessage(),
                'id' => $countryId,
            ]);

            return $this->error(
                'تعذر إتمام عملية الحذف. يرجى المحاولة لاحقاً.',
                null,
                422
            );
        }
    }

    /*
    public function index(IndexCountryRequest $request): View
    {
        $query = Country::query();

        if ($search = $request->get('search')) {
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search);
            $query->where(function (Builder $q) use ($escaped) {
                $q->where('name', 'like', "%{$escaped}%")
                  ->orWhere('iso_code', 'like', "%{$escaped}%");
            });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $countries = $query->withCount('cities')
            ->orderBy('name')
            ->paginate($request->get('per_page', 15))
            ->withQueryString();

        return view('admin.countries.index', compact('countries'));
    }

    public function create(): View
    {
        return view('admin.countries.create');
    }

    public function store(StoreCountryRequest $request): RedirectResponse|JsonResponse
    {
        try {
            DB::beginTransaction();

            $country = Country::create($request->validated());

            DB::afterCommit(function () use ($country) {
                $this->auditService->log(
                    auth()->id(),
                    'create',
                    'countries',
                    $country->id,
                    null,
                    $country->fresh()->toArray()
                );

                cache()->forget('countries-list');
            });

            DB::commit();

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'تم إضافة الدولة بنجاح']);
            }

            return redirect()->route('admin.countries.index')
                ->with('success', 'تم إضافة الدولة بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Country store failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'حدث خطأ أثناء إضافة الدولة. يرجى المحاولة مرة أخرى.'], 422);
            }

            return back()->withInput()
                ->with('error', 'حدث خطأ أثناء إضافة الدولة. يرجى المحاولة مرة أخرى.');
        }
    }

    public function edit(Country $country): View
    {
        return view('admin.countries.edit', compact('country'));
    }

    public function update(UpdateCountryRequest $request, Country $country): RedirectResponse|JsonResponse
    {
        $oldData = $country->toArray();

        try {
            DB::beginTransaction();

            $country->update($request->validated());

            DB::afterCommit(function () use ($country, $oldData) {
                $this->auditService->log(
                    auth()->id(),
                    'update',
                    'countries',
                    $country->id,
                    $oldData,
                    $country->fresh()->toArray()
                );

                cache()->forget('countries-list');
            });

            DB::commit();

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'تم تحديث الدولة بنجاح']);
            }

            return redirect()->route('admin.countries.index')
                ->with('success', 'تم تحديث الدولة بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Country update failed', [
                'error' => $e->getMessage(),
                'id'    => $country->id,
            ]);

            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'حدث خطأ أثناء تحديث الدولة. يرجى المحاولة مرة أخرى.'], 422);
            }

            return back()->withInput()
                ->with('error', 'حدث خطأ أثناء تحديث الدولة. يرجى المحاولة مرة أخرى.');
        }
    }

    public function destroy(Request $request, Country $country): RedirectResponse|JsonResponse
    {
        if ($country->cities()->exists()) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'لا يمكن حذف دولة مرتبطة بمدن'], 422);
            }
            return redirect()->route('admin.countries.index')
                ->with('error', 'لا يمكن حذف دولة مرتبطة بمدن');
        }

        $countryId = $country->id;
        $oldData = $country->toArray();

        try {
            DB::beginTransaction();

            $country->delete();

            DB::afterCommit(function () use ($countryId, $oldData) {
                $this->auditService->log(
                    auth()->id(),
                    'delete',
                    'countries',
                    $countryId,
                    $oldData,
                    null
                );

                cache()->forget('countries-list');
            });

            DB::commit();

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'تم حذف الدولة بنجاح']);
            }

            return redirect()->route('admin.countries.index')
                ->with('success', 'تم حذف الدولة بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Country destroy failed', [
                'error' => $e->getMessage(),
                'id'    => $countryId,
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
