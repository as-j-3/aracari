<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\CountryTax\IndexCountryTaxRequest;
use App\Http\Requests\Web\CountryTax\StoreCountryTaxRequest;
use App\Http\Requests\Web\CountryTax\UpdateCountryTaxRequest;
use App\Http\Resources\Admin\CountryTaxResource;
use App\Models\CountryTax;
use App\Services\AuditService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CountryTaxController extends Controller
{
    public function __construct(
        protected AuditService $auditService
    ) {
        $this->middleware('require.admin');
    }

    /**
     * عرض ضرائب الدول
     */
    public function index(IndexCountryTaxRequest $request): JsonResponse
    {
        $perPage = $request->validated('per_page', 15);
        $search = $request->validated('search');
        $countryId = $request->validated('country_id');
        $taxId = $request->validated('tax_id');
        $type = $request->validated('type');

        $query = CountryTax::query()
            ->with([
                'tax',
                'country',
            ]);

        /*
        |--------------------------------------------------------------------------
        | البحث
        |--------------------------------------------------------------------------
        */

        if ($search !== null && $search !== '') {
            $safeSearch = str_replace(
                ['\\', '%', '_'],
                ['\\\\', '\\%', '\\_'],
                $search
            );

            $query->where(function (Builder $q) use ($safeSearch) {
                $q->whereHas('tax', function (Builder $taxQuery) use ($safeSearch) {
                    $taxQuery->where(
                        'name',
                        'like',
                        "%{$safeSearch}%"
                    );
                })->orWhereHas('country', function (Builder $countryQuery) use ($safeSearch) {
                    $countryQuery
                        ->where('name', 'like', "%{$safeSearch}%")
                        ->orWhere('iso_code', 'like', "%{$safeSearch}%");
                });
            });
        }

        /*
        |--------------------------------------------------------------------------
        | الفلاتر
        |--------------------------------------------------------------------------
        */

        if ($countryId !== null) {
            $query->where('country_id', $countryId);
        }

        if ($taxId !== null) {
            $query->where('tax_id', $taxId);
        }

        if ($type !== null) {
            $query->where('type', $type);
        }

        if ($request->has('is_active')) {
            $query->where(
                'is_active',
                $request->boolean('is_active')
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Pagination
        |--------------------------------------------------------------------------
        */

        $countryTaxes = $query
            ->latest('created_at')
            ->paginate($perPage)
            ->withQueryString();

        return $this->success(
            CountryTaxResource::collection($countryTaxes),
            'تم استرجاع ضرائب الدول بنجاح'
        );
    }

    /**
     * إضافة ضريبة لدولة
     */
    public function store(StoreCountryTaxRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();

            if ($data['type'] === 'percentage') {
                $data['amount'] = null;
            }

            if ($data['type'] === 'fixed') {
                $data['rate'] = null;
            }

            $data['is_active'] = $data['is_active'] ?? true;

            $countryTax = CountryTax::create($data);

            DB::commit();

            $countryTax->load([
                'tax',
                'country',
            ]);

            $this->auditService->log(
                auth()->id(),
                'create',
                'country_taxes',
                $countryTax->id,
                null,
                $countryTax->toArray()
            );

            return $this->success(
                new CountryTaxResource($countryTax),
                'تم إضافة ضريبة الدولة بنجاح'
            );

        } catch (\Exception $e) {

            DB::rollBack();

            Log::error('CountryTax creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(
                'حدث خطأ أثناء إضافة ضريبة الدولة. يرجى المحاولة مرة أخرى.',
                null,
                422
            );
        }
    }

    /**
     * جلب ضريبة دولة واحدة باستخدام UUID
     */
    public function show(CountryTax $countryTax): JsonResponse
    {
        $countryTax->load([
            'tax',
            'country',
        ]);

        return $this->success(
            new CountryTaxResource($countryTax),
            'تم استرجاع ضريبة الدولة بنجاح'
        );
    }

    /**
     * تعديل ضريبة دولة باستخدام UUID
     */
    public function update(
        UpdateCountryTaxRequest $request,
        CountryTax $countryTax
    ): JsonResponse {
        try {
            DB::beginTransaction();

            $oldData = $countryTax->toArray();

            $data = $request->validated();

            $type = $data['type'] ?? $countryTax->type;

            if ($type === 'percentage') {
                $data['amount'] = null;
            }

            if ($type === 'fixed') {
                $data['rate'] = null;
            }

            $countryTax->update($data);

            $newData = $countryTax->fresh()->toArray();

            $changes = array_diff_assoc(
                $newData,
                $oldData
            );

            DB::commit();

            if (! empty($changes)) {
                $this->auditService->log(
                    auth()->id(),
                    'update',
                    'country_taxes',
                    $countryTax->id,
                    array_intersect_key($oldData, $changes),
                    $changes
                );
            }

            $countryTax->load([
                'tax',
                'country',
            ]);

            return $this->success(
                new CountryTaxResource($countryTax),
                'تم تحديث ضريبة الدولة بنجاح'
            );

        } catch (\Exception $e) {

            DB::rollBack();

            Log::error('CountryTax update failed', [
                'error' => $e->getMessage(),
                'id' => $countryTax->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(
                'حدث خطأ أثناء تحديث ضريبة الدولة. يرجى المحاولة مرة أخرى.',
                null,
                422
            );
        }
    }

    /**
     * حذف ضريبة دولة باستخدام UUID
     */
    public function destroy(CountryTax $countryTax): JsonResponse
    {
        if ($countryTax->providerTaxes()->exists()) {
            return $this->error(
                'لا يمكن حذف ضريبة الدولة لأنها مرتبطة بضرائب المزودين',
                null,
                422
            );
        }

        if ($countryTax->commissionTaxes()->exists()) {
            return $this->error(
                'لا يمكن حذف ضريبة الدولة لأنها مرتبطة بضرائب العمولات',
                null,
                422
            );
        }

        try {
            DB::beginTransaction();

            $oldData = $countryTax->toArray();
            $countryTaxId = $countryTax->id;

            $countryTax->delete();

            DB::commit();

            $this->auditService->log(
                auth()->id(),
                'delete',
                'country_taxes',
                $countryTaxId,
                $oldData,
                null
            );

            return $this->success(
                null,
                'تم حذف ضريبة الدولة بنجاح'
            );

        } catch (\Exception $e) {

            DB::rollBack();

            Log::error('CountryTax deletion failed', [
                'error' => $e->getMessage(),
                'id' => $countryTaxId,
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(
                'تعذر إتمام عملية الحذف. يرجى المحاولة لاحقاً.',
                null,
                422
            );
        }
    }
}
