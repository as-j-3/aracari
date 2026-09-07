<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Tax\AdminIndexTaxRequest;
use App\Http\Requests\Web\Tax\StoreTaxRequest;
use App\Http\Requests\Web\Tax\UpdateTaxRequest;
use App\Http\Resources\Admin\TaxResource;
use App\Models\Tax;
use App\Services\AuditService;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TaxController extends Controller
{
    public function __construct(
        protected AuditService $auditService,
    ) {
        $this->middleware('require.admin');
    }

    public function index(AdminIndexTaxRequest $request): JsonResponse
    {
        $perPage = $request->validated('per_page', 15);
        $search = $request->validated('search');

        $query = Tax::query();

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
                $q->where('name', 'like', "%{$safeSearch}%")
                    ->orWhere('uuid', 'like', "%{$safeSearch}%");
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Pagination
        |--------------------------------------------------------------------------
        */

        $taxes = $query
            ->latest('created_at')
            ->paginate($perPage)
            ->withQueryString();

        return $this->success(
            TaxResource::collection($taxes),
            'تم استرجاع الضرائب بنجاح'
        );
    }

    /**
     * إضافة ضريبة
     */
    public function store(StoreTaxRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $tax = Tax::create([
                ...$request->validated(),
                'created_by' => auth()->id(),
                'uuid' => (string) Str::uuid(),
            ]);

            $taxData = $tax->fresh()->toArray();
            $taxId = $tax->id;

            DB::commit();

            /*
            |--------------------------------------------------------------------------
            | Audit
            |--------------------------------------------------------------------------
            */

            $this->auditService->log(
                auth()->id(),
                'create',
                'taxes',
                $taxId,
                null,
                $taxData
            );

            return $this->success(
                $tax->fresh(),
                'تم إضافة الضريبة بنجاح'
            );

        } catch (\Exception $e) {

            DB::rollBack();

            Log::error('Tax creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(
                'حدث خطأ أثناء إضافة الضريبة. يرجى المحاولة مرة أخرى.',
                null,
                422
            );
        }
    }

    /**
     * تعديل ضريبة
     */
    public function update(UpdateTaxRequest $request, string $uuid): JsonResponse
    {
        $tax = Tax::where('uuid', $uuid)->first();

        if (! $tax) {
            return $this->error(
                'الضريبة غير موجودة',
                null,
                404
            );
        }

        try {
            DB::beginTransaction();

            $oldData = $tax->toArray();

            $tax->update(
                $request->validated()
            );

            $newData = $tax->fresh()->toArray();

            $changes = array_diff_assoc(
                $newData,
                $oldData
            );

            DB::commit();

            /*
            |--------------------------------------------------------------------------
            | Audit
            |--------------------------------------------------------------------------
            */

            if (! empty($changes)) {
                $this->auditService->log(
                    auth()->id(),
                    'update',
                    'taxes',
                    $tax->id,
                    array_intersect_key($oldData, $changes),
                    $changes
                );
            }

            return $this->success(
                $tax->fresh(),
                'تم تحديث الضريبة بنجاح'
            );

        } catch (\Exception $e) {

            DB::rollBack();

            Log::error('Tax update failed', [
                'error' => $e->getMessage(),
                'id' => $tax->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(
                'حدث خطأ أثناء تحديث الضريبة. يرجى المحاولة مرة أخرى.',
                null,
                422
            );
        }
    }

    /**
     * حذف ضريبة
     */
    public function destroy(string $uuid): JsonResponse
    {
        $tax = Tax::where('uuid', $uuid)->first();

        if (! $tax) {
            return $this->error(
                'الضريبة غير موجودة',
                null,
                404
            );
        }

        /*
        |--------------------------------------------------------------------------
        | منع الحذف إذا كانت الضريبة مرتبطة بحجوزات
        |--------------------------------------------------------------------------
        */

        if ($tax->bookingTaxes()->exists()) {
            return $this->error(
                'لا يمكن حذف ضريبة مرتبطة بحجوزات',
                null,
                422
            );
        }

        /*
        |--------------------------------------------------------------------------
        | منع الحذف إذا كانت مرتبطة بإعدادات الضرائب
        |--------------------------------------------------------------------------
        */

        if ($tax->countryTaxes()->exists()) {
            return $this->error(
                'لا يمكن حذف ضريبة مرتبطة بإعدادات ضريبية',
                null,
                422
            );
        }

        try {
            DB::beginTransaction();

            $oldData = $tax->toArray();
            $taxId = $tax->id;

            $tax->delete();

            DB::commit();

            /*
            |--------------------------------------------------------------------------
            | Audit
            |--------------------------------------------------------------------------
            */

            $this->auditService->log(
                auth()->id(),
                'delete',
                'taxes',
                $taxId,
                $oldData,
                null
            );

            return $this->success(
                null,
                'تم حذف الضريبة بنجاح'
            );

        } catch (\Exception $e) {

            DB::rollBack();

            Log::error('Tax deletion failed', [
                'error' => $e->getMessage(),
                'id' => $taxId,
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
        public function index(AdminIndexTaxRequest $request): View
        {
            $perPage = $request->validated('per_page', 15);
            $search  = $request->validated('search');
            $type    = $request->validated('type');

            $query = Tax::query();

            if ($search !== null && $search !== '') {
                $safeSearch = str_replace(['%', '_'], ['\\%', '\\_'], $search);
                $query->where(function (Builder $q) use ($safeSearch) {
                    $q->where('name', 'like', "%{$safeSearch}%")
                      ->orWhere('uuid', 'like', "%{$safeSearch}%");
                });
            }

            if ($type !== null && $type !== '') {
                $allowedTypes = ['percentage', 'fixed'];
                if (in_array($type, $allowedTypes, true)) {
                    $query->where('type', $type);
                }
            }

            $taxes = $query->latest()->paginate($perPage);

            $stats = [
                'total'      => Tax::count(),
                'percentage' => Tax::where('type', 'percentage')->count(),
                'fixed'      => Tax::where('type', 'fixed')->count(),
            ];

            $highestTax = Tax::orderByDesc('rate')->orderByDesc('amount')->first();

            return view('admin.taxes.index', compact('taxes', 'stats', 'highestTax'));
        }

        public function store(StoreTaxRequest $request): JsonResponse|RedirectResponse
        {
            try {
                DB::beginTransaction();
                $tax = Tax::create($request->validated() + ['created_by' => auth()->id()]);
                $taxId = $tax->id;
                $taxData = $tax->toArray();
                DB::afterCommit(function () use ($taxId, $taxData) {
                    $this->auditService->log(auth()->id(), 'create', 'taxes', $taxId, null, $taxData);
                });
                DB::commit();

                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'تم إضافة الضريبة بنجاح',
                    ]);
                }

                return redirect()->route('admin.taxes.index')->with('success', 'تم إضافة الضريبة بنجاح');
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Tax creation failed: ' . $e->getMessage());

                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'حدث خطأ أثناء إضافة الضريبة. يرجى المحاولة مرة أخرى.',
                        'errors' => ['name' => [$e->getMessage()]],
                    ], 422);
                }

                return back()->withInput()->with('error', 'حدث خطأ أثناء إضافة الضريبة. يرجى المحاولة مرة أخرى.');
            }
        }

        public function update(UpdateTaxRequest $request, string $uuid): JsonResponse|RedirectResponse
        {
            $tax = Tax::where('uuid', $uuid)->first();
            abort_unless($tax, 404, 'الضريبة غير موجودة');

            try {
                DB::beginTransaction();
                $oldData = $tax->toArray();
                $tax->update($request->validated());
                $newData = $tax->fresh()->toArray();

                $changes = array_diff_assoc($newData, $oldData);
                if (!empty($changes)) {
                    $taxId = $tax->id;
                    $oldSubset = array_intersect_key($oldData, $changes);
                    DB::afterCommit(function () use ($taxId, $oldSubset, $changes) {
                        $this->auditService->log(
                            auth()->id(),
                            'update',
                            'taxes',
                            $taxId,
                            $oldSubset,
                            $changes
                        );
                    });
                }
                DB::commit();

                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'تم تحديث الضريبة بنجاح',
                    ]);
                }

                return redirect()->route('admin.taxes.index')->with('success', 'تم تحديث الضريبة بنجاح');
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Tax update failed: ' . $e->getMessage());

                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'حدث خطأ أثناء تحديث الضريبة. يرجى المحاولة مرة أخرى.',
                        'errors' => ['name' => [$e->getMessage()]],
                    ], 422);
                }

                return back()->withInput()->with('error', 'حدث خطأ أثناء تحديث الضريبة. يرجى المحاولة مرة أخرى.');
            }
        }

        public function destroy(Request $request, string $uuid): JsonResponse|RedirectResponse
        {
            $tax = Tax::where('uuid', $uuid)->first();
            abort_unless($tax, 404, 'الضريبة غير موجودة');

            if ($tax->bookingTaxes()->exists()) {
                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'لا يمكن حذف ضريبة مرتبطة بحجوزات',
                    ], 422);
                }

                return back()->with('error', 'لا يمكن حذف ضريبة مرتبطة بحجوزات');
            }

            try {
                DB::beginTransaction();
                $oldData = $tax->toArray();
                $taxId = $tax->id;

                $tax->delete();

                DB::afterCommit(function () use ($taxId, $oldData) {
                    $this->auditService->log(auth()->id(), 'delete', 'taxes', $taxId, $oldData, null);
                });

                DB::commit();

                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'تم حذف الضريبة بنجاح',
                    ]);
                }

                return redirect()->route('admin.taxes.index')->with('success', 'تم حذف الضريبة بنجاح');
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Tax deletion failed: ' . $e->getMessage());

                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'تعذر إتمام عملية الحذف. يرجى المحاولة لاحقاً.',
                    ], 422);
                }

                return back()->with('error', 'تعذر إتمام عملية الحذف. يرجى المحاولة لاحقاً.');
            }
        }
    */
}
