<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\CommissionTax\IndexCommissionTaxRequest;
use App\Http\Requests\Web\CommissionTax\StoreCommissionTaxRequest;
use App\Http\Requests\Web\CommissionTax\UpdateCommissionTaxRequest;
use App\Http\Resources\Admin\CommissionTaxResource;
use App\Models\CommissionTax;
use App\Services\AuditService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CommissionTaxController extends Controller
{
    public function __construct(
        protected AuditService $auditService
    ) {
        $this->middleware('require.admin');
    }

    /**
     * عرض ضرائب العمولات
     */
    public function index(IndexCommissionTaxRequest $request): JsonResponse
    {
        $perPage = $request->validated('per_page', 15);
        $search = $request->validated('search');
        $countryTaxId = $request->validated('country_tax_id');
        $serviceId = $request->validated('service_id');

        $query = CommissionTax::query()
            ->with([
                'countryTax.tax',
                'countryTax.country',
                'service',
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
                $q->whereHas('countryTax.tax', function (Builder $taxQuery) use ($safeSearch) {
                    $taxQuery->where('name', 'like', "%{$safeSearch}%");
                })->orWhereHas('countryTax.country', function (Builder $countryQuery) use ($safeSearch) {
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

        if ($countryTaxId !== null) {
            $query->where('country_tax_id', $countryTaxId);
        }

        if ($serviceId !== null) {
            $query->where('service_id', $serviceId);
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

        $commissionTaxes = $query
            ->latest('created_at')
            ->paginate($perPage)
            ->withQueryString();

        return $this->success(
            CommissionTaxResource::collection($commissionTaxes),
            'تم استرجاع ضرائب العمولات بنجاح'
        );
    }

    /**
     * إضافة ضريبة عمولة
     */
    public function store(StoreCommissionTaxRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();
            $data['is_active'] = $data['is_active'] ?? true;

            $commissionTax = CommissionTax::create($data);

            DB::commit();

            $commissionTax->load([
                'countryTax.tax',
                'countryTax.country',
                'service',
            ]);

            $this->auditService->log(
                auth()->id(),
                'create',
                'commission_taxes',
                $commissionTax->id,
                null,
                $commissionTax->toArray()
            );

            return $this->success(
                new CommissionTaxResource($commissionTax),
                'تم إضافة ضريبة العمولة بنجاح'
            );

        } catch (\Exception $e) {

            DB::rollBack();

            Log::error('CommissionTax creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(
                'حدث خطأ أثناء إضافة ضريبة العمولة. يرجى المحاولة مرة أخرى.',
                null,
                422
            );
        }
    }

    /**
     * جلب ضريبة عمولة واحدة باستخدام UUID
     */
    public function show(CommissionTax $commissionTax): JsonResponse
    {
        $commissionTax->load([
            'countryTax.tax',
            'countryTax.country',
            'service',
        ]);

        return $this->success(
            new CommissionTaxResource($commissionTax),
            'تم استرجاع ضريبة العمولة بنجاح'
        );
    }

    /**
     * تعديل ضريبة عمولة باستخدام UUID
     */
    public function update(
        UpdateCommissionTaxRequest $request,
        CommissionTax $commissionTax
    ): JsonResponse {
        try {
            DB::beginTransaction();

            $oldData = $commissionTax->toArray();
            $data = $request->validated();

            $commissionTax->update($data);

            $newData = $commissionTax->fresh()->toArray();

            $changes = array_diff_assoc($newData, $oldData);

            DB::commit();

            if (!empty($changes)) {
                $this->auditService->log(
                    auth()->id(),
                    'update',
                    'commission_taxes',
                    $commissionTax->id,
                    array_intersect_key($oldData, $changes),
                    $changes
                );
            }

            $commissionTax->load([
                'countryTax.tax',
                'countryTax.country',
                'service',
            ]);

            return $this->success(
                new CommissionTaxResource($commissionTax),
                'تم تحديث ضريبة العمولة بنجاح'
            );

        } catch (\Exception $e) {

            DB::rollBack();

            Log::error('CommissionTax update failed', [
                'error' => $e->getMessage(),
                'id' => $commissionTax->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(
                'حدث خطأ أثناء تحديث ضريبة العمولة. يرجى المحاولة مرة أخرى.',
                null,
                422
            );
        }
    }

    /**
     * حذف ضريبة عمولة باستخدام UUID
     */
    public function destroy(CommissionTax $commissionTax): JsonResponse
    {
        try {
            DB::beginTransaction();

            $oldData = $commissionTax->toArray();
            $commissionTaxId = $commissionTax->id;

            $commissionTax->delete();

            DB::commit();

            $this->auditService->log(
                auth()->id(),
                'delete',
                'commission_taxes',
                $commissionTaxId,
                $oldData,
                null
            );

            return $this->success(
                null,
                'تم حذف ضريبة العمولة بنجاح'
            );

        } catch (\Exception $e) {

            DB::rollBack();

            Log::error('CommissionTax deletion failed', [
                'error' => $e->getMessage(),
                'id' => $commissionTaxId,
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
