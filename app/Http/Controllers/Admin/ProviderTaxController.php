<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\ProviderTax\IndexProviderTaxRequest;
use App\Http\Requests\Web\ProviderTax\StoreProviderTaxRequest;
use App\Http\Requests\Web\ProviderTax\UpdateProviderTaxRequest;
use App\Http\Resources\Admin\ProviderTaxResource;
use App\Models\ProviderTax;
use App\Models\Service;
use App\Services\AuditService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProviderTaxController extends Controller
{
    public function __construct(
        protected AuditService $auditService
    ) {
        // عمليات التعديل والحذف مقتصرة على الأدمن فقط
        $this->middleware('require.admin')->only(['update', 'destroy']);
    }

    /**
     * عرض ضرائب المزودين
     */
    public function index(IndexProviderTaxRequest $request): JsonResponse
    {
        $user = auth()->user();

        // التحقق من صلاحية الوصول: أدمن أو مزود خدمة
        if (!$user->isAdmin() && !$user->isProvider()) {
            return $this->error('غير مصرح لك بالوصول.', null, 403);
        }

        $perPage = $request->validated('per_page', 15);
        $search = $request->validated('search');
        $countryTaxId = $request->validated('country_tax_id');
        $serviceId = $request->validated('service_id');

        $query = ProviderTax::query()
            ->with([
                'countryTax.tax',
                'countryTax.country',
                'service.branch',
            ]);

        /*
        |--------------------------------------------------------------------------
        | عزل البيانات في حال كان المستخدم مزود خدمة
        |--------------------------------------------------------------------------
        */
        if ($user->isProvider() && !$user->isAdmin()) {
            $providerServiceId = $user->providerService?->id;

            $query->where(function (Builder $q) use ($providerServiceId) {
                $q->whereNull('service_id')
                  ->orWhereHas('service.branch', function (Builder $bQuery) use ($providerServiceId) {
                      $bQuery->where('provider_id', $providerServiceId);
                  });
            });
        }

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

        $providerTaxes = $query
            ->latest('created_at')
            ->paginate($perPage)
            ->withQueryString();

        return $this->success(
            ProviderTaxResource::collection($providerTaxes),
            'تم استرجاع ضرائب المزودين بنجاح'
        );
    }

    /**
     * إضافة ضريبة مزود (متاح للأدمن ولمزود الخدمة)
     */
    public function store(StoreProviderTaxRequest $request): JsonResponse
    {
        $user = auth()->user();

        // التحقق من الصلاحية
        if (!$user->isAdmin() && !$user->isProvider()) {
            return $this->error('غير مصرح لك بإضافة ضريبة مزود.', null, 403);
        }

        try {
            DB::beginTransaction();

            $data = $request->validated();
            $data['is_active'] = $data['is_active'] ?? true;

            // إذا كان المستخدم مزود خدمة، نتأكد أن الخدمة تابعة له
            if ($user->isProvider() && !$user->isAdmin()) {
                if (!empty($data['service_id'])) {
                    $service = Service::with('branch')->find($data['service_id']);
                    $providerServiceId = $user->providerService?->id;

                    if (!$service || $service->branch?->provider_id !== $providerServiceId) {
                        DB::rollBack();
                        return $this->error('لا يمكنك إضافة ضريبة لخدمة لا تتبع حسابك.', null, 403);
                    }
                }
            }

            $providerTax = ProviderTax::create($data);

            DB::commit();

            $providerTax->load([
                'countryTax.tax',
                'countryTax.country',
                'service',
            ]);

            $this->auditService->log(
                auth()->id(),
                'create',
                'provider_taxes',
                $providerTax->id,
                null,
                $providerTax->toArray()
            );

            return $this->success(
                new ProviderTaxResource($providerTax),
                'تم إضافة ضريبة المزود بنجاح'
            );

        } catch (\Exception $e) {

            DB::rollBack();

            Log::error('ProviderTax creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(
                'حدث خطأ أثناء إضافة ضريبة المزود. يرجى المحاولة مرة أخرى.',
                null,
                422
            );
        }
    }

    /**
     * جلب ضريبة مزود واحدة باستخدام UUID
     */
    public function show(ProviderTax $providerTax): JsonResponse
    {
        $user = auth()->user();

        if (!$user->isAdmin() && !$user->isProvider()) {
            return $this->error('غير مصرح لك بالوصول.', null, 403);
        }

        // إذا كان مزود خدمة، نتأكد من ارتباط السجل به إذا كان مرتبطاً بخدمة
        if ($user->isProvider() && !$user->isAdmin() && $providerTax->service_id) {
            $providerServiceId = $user->providerService?->id;
            $service = $providerTax->service()->with('branch')->first();

            if ($service && $service->branch?->provider_id !== $providerServiceId) {
                return $this->error('غير مصرح لك بعرض هذه الضريبة.', null, 403);
            }
        }

        $providerTax->load([
            'countryTax.tax',
            'countryTax.country',
            'service',
        ]);

        return $this->success(
            new ProviderTaxResource($providerTax),
            'تم استرجاع ضريبة المزود بنجاح'
        );
    }

    /**
     * تعديل ضريبة مزود باستخدام UUID (خاص بالأدمن فقط)
     */
    public function update(
        UpdateProviderTaxRequest $request,
        ProviderTax $providerTax
    ): JsonResponse {
        try {
            DB::beginTransaction();

            $oldData = $providerTax->toArray();
            $data = $request->validated();

            $providerTax->update($data);

            $newData = $providerTax->fresh()->toArray();

            $changes = array_diff_assoc($newData, $oldData);

            DB::commit();

            if (!empty($changes)) {
                $this->auditService->log(
                    auth()->id(),
                    'update',
                    'provider_taxes',
                    $providerTax->id,
                    array_intersect_key($oldData, $changes),
                    $changes
                );
            }

            $providerTax->load([
                'countryTax.tax',
                'countryTax.country',
                'service',
            ]);

            return $this->success(
                new ProviderTaxResource($providerTax),
                'تم تحديث ضريبة المزود بنجاح'
            );

        } catch (\Exception $e) {

            DB::rollBack();

            Log::error('ProviderTax update failed', [
                'error' => $e->getMessage(),
                'id' => $providerTax->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(
                'حدث خطأ أثناء تحديث ضريبة المزود. يرجى المحاولة مرة أخرى.',
                null,
                422
            );
        }
    }

    /**
     * حذف ضريبة مزود باستخدام UUID (خاص بالأدمن فقط)
     */
    public function destroy(ProviderTax $providerTax): JsonResponse
    {
        try {
            DB::beginTransaction();

            $oldData = $providerTax->toArray();
            $providerTaxId = $providerTax->id;

            $providerTax->delete();

            DB::commit();

            $this->auditService->log(
                auth()->id(),
                'delete',
                'provider_taxes',
                $providerTaxId,
                $oldData,
                null
            );

            return $this->success(
                null,
                'تم حذف ضريبة المزود بنجاح'
            );

        } catch (\Exception $e) {

            DB::rollBack();

            Log::error('ProviderTax deletion failed', [
                'error' => $e->getMessage(),
                'id' => $providerTaxId,
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
