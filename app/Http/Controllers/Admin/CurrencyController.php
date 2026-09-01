<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Http\Requests\Web\Currency\StoreCurrencyRequest;
use App\Http\Requests\Web\Currency\UpdateCurrencyRequest;
use App\Http\Requests\Web\Currency\IndexCurrencyRequest;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Builder;

class CurrencyController extends Controller
{
    public function __construct(
        private AuditService $auditService
    ) {
        $this->middleware('require.admin');
    }

    public function index(IndexCurrencyRequest $request): View
    {
        $query = Currency::query();

        if ($search = $request->get('search')) {
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search);
            $query->where(function (Builder $q) use ($escaped) {
                $q->where('name', 'like', "%{$escaped}%")
                  ->orWhere('code', 'like', "%{$escaped}%")
                  ->orWhere('symbol', 'like', "%{$escaped}%");
            });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $currencies = $query->withCount(['bookings', 'transactions', 'trips'])
            ->orderBy('name')
            ->paginate($request->get('per_page', 20))
            ->withQueryString();

        return view('admin.currencies.index', compact('currencies'));
    }

    public function create(): View
    {
        return view('admin.currencies.create');
    }

    public function store(StoreCurrencyRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        try {
            DB::beginTransaction(); 

            $currency = Currency::create($validated);

            DB::afterCommit(function () use ($currency) {
                $this->auditService->log(
                    auth()->id(),
                    'create',
                    'currencies',
                    $currency->id,
                    null,
                    $currency->fresh()->toArray()
                );

                $this->clearCurrencyCache();
            });

            DB::commit(); 

            return redirect()
                ->route('admin.currencies.index')
                ->with('success', 'تم إضافة العملة بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Currency store failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->back()->withInput()
                ->with('error', 'تعذر إتمام العملية. يرجى المحاولة مرة أخرى.');
        }
    }

    public function edit(Currency $currency): View
    {
        return view('admin.currencies.edit', compact('currency'));
    }

    public function update(UpdateCurrencyRequest $request, Currency $currency): RedirectResponse
    {
        $validated = $request->validated();
        $oldData = $currency->toArray();
        $currencyId = $currency->id;

        try {
            DB::beginTransaction();

            $currency->update($validated);

            DB::afterCommit(function () use ($currency, $oldData, $currencyId) {
                $this->auditService->log(
                    auth()->id(),
                    'update',
                    'currencies',
                    $currencyId,
                    $oldData,
                    $currency->fresh()->toArray()
                );

                $this->clearCurrencyCache($currencyId);
            });

            DB::commit();

            return redirect()
                ->route('admin.currencies.index')
                ->with('success', 'تم تحديث العملة بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Currency update failed', [
                'error' => $e->getMessage(),
                'id'    => $currency->id,
            ]);
            return redirect()->back()
                ->with('error', 'تعذر إتمام العملية. يرجى المحاولة مرة أخرى.');
        }
    }


    public function destroy(Currency $currency): JsonResponse
    {
        $linkedCount = $currency->bookings()->count()
            + $currency->transactions()->count()
            + $currency->trips()->count();

        if ($linkedCount > 0) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف العملة لأنها مرتبطة بـ' . $linkedCount . ' سجل (حجوزات/معاملات/رحلات). قم بنقل أو حذف السجلات المرتبطة أولاً.',
            ], 422);
        }

        try {
            DB::beginTransaction();
            $oldData = $currency->toArray();
            $currencyId = $currency->id;
            $currency->delete();

            DB::afterCommit(function () use ($currencyId, $oldData) {
                $this->auditService->log(
                    auth()->id(),
                    'delete',
                    'currencies',
                    $currencyId,
                    $oldData,
                    null
                );

                $this->clearCurrencyCache($currencyId);
            });

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف العملة بنجاح',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Currency destroy failed', [
                'error' => $e->getMessage(),
                'id'    => $currency->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'تعذر إتمام عملية الحذف. يرجى المحاولة لاحقاً.',
            ], 500);
        }
    }


    private function clearCurrencyCache(?int $currencyId = null): void
    {
        cache()->forget('currencies-list');

        if ($currencyId) {
            cache()->forget("currency_precision:{$currencyId}");
        }
        cache()->forget('base_currency_id');
    }
}