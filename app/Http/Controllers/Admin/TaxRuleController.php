<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\TaxRule\AdminIndexTaxRuleRequest;
use App\Http\Requests\Web\TaxRule\StoreTaxRuleRequest;
use App\Http\Requests\Web\TaxRule\UpdateTaxRuleRequest;
use App\Models\TaxRule;
use App\Models\Tax;
use App\Models\Country;
use App\Models\ProviderService;
use App\Models\TypeOfService;
use App\Models\PaymentMethod;
use App\Models\Trip;
use App\Models\Currency;
use App\Services\AuditService;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TaxRuleController extends Controller
{
    public function __construct(
        private AuditService $auditService
    ) {
        $this->middleware('require.admin');
    }

    public function index(AdminIndexTaxRuleRequest $request): View
    {
        $perPage = $request->validated('per_page', 15);
        $search  = $request->validated('search');
        $status  = $request->validated('status');

        $query = TaxRule::with(['tax', 'country', 'provider', 'trip', 'currency', 'serviceType', 'paymentMethod']);

        if ($search !== null && $search !== '') {
            $safeSearch = str_replace(['%', '_'], ['\\%', '\\_'], $search);
            $query->where(function (Builder $q) use ($safeSearch) {
                $q->whereHas('tax', function (Builder $tq) use ($safeSearch) {
                    $tq->where('name', 'like', "%{$safeSearch}%");
                })
                ->orWhereHas('country', function (Builder $cq) use ($safeSearch) {
                    $cq->where('name', 'like', "%{$safeSearch}%");
                })
                ->orWhereHas('provider', function (Builder $pq) use ($safeSearch) {
                    $pq->where('name', 'like', "%{$safeSearch}%");
                })
                ->orWhereHas('trip', function (Builder $tq) use ($safeSearch) {
                    $tq->where('trip_number', 'like', "%{$safeSearch}%");
                })
                ->orWhere('id', 'like', "%{$safeSearch}%");
            });
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $taxRules = $query->latest()->paginate($perPage);

        $stats = [
            'total'  => TaxRule::count(),
            'active' => TaxRule::where('is_active', true)->count(),
            'inactive' => TaxRule::where('is_active', false)->count(),
            'with_limits' => TaxRule::whereNotNull('min_amount')->orWhereNotNull('max_amount')->count(),
        ];

        $formData = $this->getTaxRuleFormData();

        return view('admin.tax-rules.index', compact('taxRules', 'stats') + $formData);
    }

    public function store(StoreTaxRuleRequest $request): JsonResponse|RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);
        $data['created_by_user'] = auth()->id();

        try {
            DB::beginTransaction();
            $taxRule = TaxRule::create($data);

            $taxRuleId = $taxRule->id;
            $taxRuleData = $taxRule->toArray();
            DB::afterCommit(function () use ($taxRuleId, $taxRuleData) {
                $this->auditService->log(auth()->id(), 'created', 'tax_rules', $taxRuleId, null, $taxRuleData);
            });
            DB::commit();

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم إضافة القاعدة الضريبية بنجاح',
                ]);
            }

            return redirect()->route('admin.tax-rules.index')->with('success', 'تم إضافة القاعدة الضريبية بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TaxRule store failed: ' . $e->getMessage());

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء إضافة القاعدة. يرجى المحاولة مرة أخرى.',
                    'errors' => ['tax_id' => [$e->getMessage()]],
                ], 422);
            }

            return back()->withInput()->with('error', 'حدث خطأ أثناء إضافة القاعدة. يرجى المحاولة مرة أخرى.');
        }
    }

    public function update(UpdateTaxRuleRequest $request, int $taxRule): JsonResponse|RedirectResponse
    {
        $rule = TaxRule::findOrFail($taxRule);
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        try {
            DB::beginTransaction();

            $oldData = $rule->toArray();
            $rule->update($data);
            $newData = $rule->fresh()->toArray();

            $changes = array_diff_assoc($newData, $oldData);
            if (!empty($changes)) {
                $taxRuleId = $rule->id;
                $oldSubset = array_intersect_key($oldData, $changes);
                DB::afterCommit(function () use ($taxRuleId, $oldSubset, $changes) {
                    $this->auditService->log(
                        auth()->id(),
                        'update',
                        'tax_rules',
                        $taxRuleId,
                        $oldSubset,
                        $changes
                    );
                });
            }
            DB::commit();

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم تحديث القاعدة الضريبية بنجاح',
                ]);
            }

            return redirect()->route('admin.tax-rules.index')->with('success', 'تم تحديث القاعدة الضريبية بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TaxRule update failed: ' . $e->getMessage());

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء تحديث القاعدة. يرجى المحاولة مرة أخرى.',
                    'errors' => ['tax_id' => [$e->getMessage()]],
                ], 422);
            }

            return back()->withInput()->with('error', 'حدث خطأ أثناء تحديث القاعدة. يرجى المحاولة مرة أخرى.');
        }
    }

    public function destroy(Request $request, int $taxRule): JsonResponse|RedirectResponse
    {
        $rule = TaxRule::findOrFail($taxRule);

        if ($rule->tax && $rule->tax->bookingTaxes()->exists()) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن حذف هذه القاعدة لوجود حجوزات مرتبطة بها.',
                ], 422);
            }
            return back()->with('error', 'لا يمكن حذف هذه القاعدة لوجود حجوزات مرتبطة بها.');
        }

        try {
            DB::beginTransaction();
            $oldData = $rule->toArray();
            $taxRuleId = $rule->id;
            $rule->delete();

            DB::afterCommit(function () use ($taxRuleId, $oldData) {
                $this->auditService->log(auth()->id(), 'deleted', 'tax_rules', $taxRuleId, $oldData, null);
            });
            DB::commit();

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم حذف القاعدة الضريبية بنجاح',
                ]);
            }

            return redirect()->route('admin.tax-rules.index')->with('success', 'تم حذف القاعدة الضريبية بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TaxRule deletion failed: ' . $e->getMessage());

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'تعذر إتمام عملية الحذف. يرجى المحاولة لاحقاً.',
                ], 422);
            }

            return back()->with('error', 'تعذر إتمام عملية الحذف. يرجى المحاولة لاحقاً.');
        }
    }

    private function getTaxRuleFormData(): array
    {
        $taxes = Cache::remember('tax_rule_form:taxes', 3600, fn() => Tax::orderBy('name')->get());
        $countries = Cache::remember('tax_rule_form:countries', 3600, fn() => Country::orderBy('name')->get());
        $serviceTypes = Cache::remember('tax_rule_form:service_types', 3600, fn() => TypeOfService::orderBy('type_name')->get());
        $paymentMethods = Cache::remember('tax_rule_form:payment_methods', 3600, fn() => PaymentMethod::orderBy('method_name')->get());
        $currencies = Cache::remember('tax_rule_form:currencies', 3600, fn() => Currency::orderBy('name')->get());
        $providers = Cache::remember('tax_rule_form:providers', 3600, fn() => ProviderService::orderBy('name')->get());
        $trips = Trip::where('departure_time', '>', now())
            ->orderBy('departure_time')
            ->limit(100)
            ->get();

        return compact('taxes', 'countries', 'providers', 'serviceTypes', 'paymentMethods', 'trips', 'currencies');
    }
}
