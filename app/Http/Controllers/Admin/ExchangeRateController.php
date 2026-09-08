<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExchangeRate;
use App\Models\Currency;
use App\Http\Requests\Web\ExchangeRate\StoreExchangeRateRequest;
use App\Http\Requests\Web\ExchangeRate\UpdateExchangeRateRequest;
use App\Http\Requests\Web\ExchangeRate\IndexExchangeRateRequest;
use App\Http\Resources\Admin\ExchangeRateResource;
use App\Repositories\Contracts\ExchangeRateRepositoryInterface;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;

class ExchangeRateController extends Controller
{
    public function __construct(
        private AuditService $auditService,
        private ExchangeRateRepositoryInterface $exchangeRateRepository
    ) {
        $this->middleware('require.admin');
    }

    /*
    |--------------------------------------------------------------------------
    | واجهة برمجية (API) - استجابات JSON
    |--------------------------------------------------------------------------
    |
    | تم تحويل الدوال الآتية لتعمل كهيئة API باستخدام success()/error()
    | والمنطق نفسه في دوال الويب القديمة (محفوظة بالأسفل كتعليقات).
    |
    */

    public function index(IndexExchangeRateRequest $request): JsonResponse
    {
        $exchangeRates = $this->exchangeRateRepository->search(
            $request->validated(),
            $request->validated('per_page') ?? 20
        );

        return $this->success(
            ExchangeRateResource::collection($exchangeRates),
            'تم استرجاع أسعار الصرف بنجاح'
        );
    }

    public function store(StoreExchangeRateRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            DB::beginTransaction();

            $exchangeRate = $this->exchangeRateRepository->create($validated);

            DB::afterCommit(function () use ($exchangeRate) {
                $this->clearRateCache($exchangeRate);
                $this->auditService->log(
                    auth()->id(),
                    'create',
                    'exchange_rates',
                    $exchangeRate->id,
                    null,
                    $exchangeRate->fresh()->toArray()
                );
            });

            DB::commit();

            return $this->success(
                new ExchangeRateResource($exchangeRate->fresh(['fromCurrency', 'toCurrency'])),
                'تم إضافة سعر الصرف بنجاح'
            );
        } catch (QueryException $e) {
            DB::rollBack();

            if ($e->errorInfo[1] === 1062) {
                return $this->error(
                    'يوجد سعر صرف مسجل بالفعل لنفس العملات في نفس التاريخ',
                    null,
                    422
                );
            }

            Log::error('ExchangeRate store failed', ['error' => $e->getMessage()]);

            return $this->error(
                'تعذر إتمام العملية. يرجى المحاولة لاحقاً.',
                null,
                422
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ExchangeRate store failed', ['error' => $e->getMessage()]);

            return $this->error(
                'تعذر إتمام العملية. يرجى المحاولة لاحقاً.',
                null,
                422
            );
        }
    }

    public function update(UpdateExchangeRateRequest $request, int $exchangeRate): JsonResponse
    {
        $rate = $this->exchangeRateRepository->findById($exchangeRate);

        if (! $rate) {
            return $this->error(
                'سعر الصرف غير موجود',
                null,
                404
            );
        }

        $validated = $request->validated();
        $oldData = $rate->toArray();
        $oldFromCurrency = $rate->from_currency_id;
        $oldToCurrency = $rate->to_currency_id;

        try {
            DB::beginTransaction();

            $this->exchangeRateRepository->update($rate->id, $validated);

            DB::afterCommit(function () use ($rate, $oldData, $oldFromCurrency, $oldToCurrency) {
                $this->clearRateCacheByIds($oldFromCurrency, $oldToCurrency);
                $this->clearRateCache($rate);

                $this->auditService->log(
                    auth()->id(),
                    'update',
                    'exchange_rates',
                    $rate->id,
                    $oldData,
                    $rate->fresh()->toArray()
                );
            });

            DB::commit();

            return $this->success(
                new ExchangeRateResource($rate->fresh(['fromCurrency', 'toCurrency'])),
                'تم تحديث سعر الصرف بنجاح'
            );
        } catch (QueryException $e) {
            DB::rollBack();

            if ($e->errorInfo[1] === 1062) {
                return $this->error(
                    'يوجد سعر صرف مسجل بالفعل لنفس العملات في نفس التاريخ',
                    null,
                    422
                );
            }

            Log::error('ExchangeRate update failed', ['error' => $e->getMessage()]);

            return $this->error(
                'تعذر إتمام عملية التحديث. يرجى المحاولة لاحقاً.',
                null,
                422
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ExchangeRate update failed', ['error' => $e->getMessage()]);

            return $this->error(
                'تعذر إتمام عملية التحديث. يرجى المحاولة لاحقاً.',
                null,
                422
            );
        }
    }

    public function destroy(int $exchangeRate): JsonResponse
    {
        $rate = $this->exchangeRateRepository->findById($exchangeRate);

        if (! $rate) {
            return $this->error(
                'سعر الصرف غير موجود',
                null,
                404
            );
        }

        $oldData = $rate->toArray();
        $fromCurrency = $rate->from_currency_id;
        $toCurrency = $rate->to_currency_id;
        $rateId = $rate->id;

        try {
            DB::beginTransaction();

            $this->exchangeRateRepository->delete($rateId);

            DB::afterCommit(function () use ($rateId, $oldData, $fromCurrency, $toCurrency) {
                $this->clearRateCacheByIds($fromCurrency, $toCurrency);
                $this->auditService->log(
                    auth()->id(),
                    'delete',
                    'exchange_rates',
                    $rateId,
                    $oldData,
                    null
                );
            });

            DB::commit();

            return $this->success(
                null,
                'تم حذف سعر الصرف بنجاح'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ExchangeRate destroy failed', ['error' => $e->getMessage()]);

            return $this->error(
                'تعذر إتمام عملية الحذف. يرجى المحاولة لاحقاً.',
                null,
                422
            );
        }
    }

    private function clearRateCache(ExchangeRate $rate): void
    {
        $this->clearRateCacheByIds($rate->from_currency_id, $rate->to_currency_id);
    }

    private function clearRateCacheByIds(int $fromId, int $toId): void
    {
        cache()->forget("exchange_rate:{$fromId}:{$toId}");

        cache()->forget("exchange_rate:{$toId}:{$fromId}");
    }

    /*
    |--------------------------------------------------------------------------
    | دوال الويب القديمة (محفوظة كتعليقات وليست محذوفة)
    |--------------------------------------------------------------------------
    |
    | كانت تعيد صفحات Blade عبر return view() / redirect().
    | تم استبدالها بنسخ JSON أعلاه. نفس المنطق والتحقق.
    |
    */

    /*
    public function index(IndexExchangeRateRequest $request): View
    {
        $query = ExchangeRate::with(['fromCurrency', 'toCurrency']);

        if ($fromId = $request->get('from_currency_id')) {
            $query->where('from_currency_id', $fromId);
        }
        if ($toId = $request->get('to_currency_id')) {
            $query->where('to_currency_id', $toId);
        }

        if ($dateFrom = $request->get('date_from')) {
            $query->where('rate_date', '>=', $dateFrom);
        }
        if ($dateTo = $request->get('date_to')) {
            $query->where('rate_date', '<=', $dateTo);
        }

        if ($search = $request->get('search')) {
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search);
            $query->where(function (Builder $q) use ($escaped) {
                $q->whereHas('fromCurrency', fn($sq) => $sq->where('name', 'like', "%{$escaped}%"))
                  ->orWhereHas('toCurrency', fn($sq) => $sq->where('name', 'like', "%{$escaped}%"))
                  ->orWhereHas('fromCurrency', fn($sq) => $sq->where('code', 'like', "%{$escaped}%"))
                  ->orWhereHas('toCurrency', fn($sq) => $sq->where('code', 'like', "%{$escaped}%"));
            });
        }

        $exchangeRates = $query->latest('rate_date')
            ->paginate($request->get('per_page', 20))
            ->withQueryString();

        $currencies = $this->getCurrencies();

        return view('admin.exchange-rates.index', compact('exchangeRates', 'currencies'));
    }

    public function create(): View
    {
        $currencies = $this->getCurrencies();
        return view('admin.exchange-rates.create', compact('currencies'));
    }

    public function store(StoreExchangeRateRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        try {
            DB::beginTransaction();

            $exchangeRate = ExchangeRate::create($validated);

            DB::afterCommit(function () use ($exchangeRate) {
                $this->clearRateCache($exchangeRate);
                $this->auditService->log(
                    auth()->id(),
                    'create',
                    'exchange_rates',
                    $exchangeRate->id,
                    null,
                    $exchangeRate->fresh()->toArray()
                );
            });

            DB::commit();

            return redirect()->route('admin.exchange-rates.index')
                ->with('success', 'تم إضافة سعر الصرف بنجاح');
        } catch (QueryException $e) {
            DB::rollBack();

            if ($e->errorInfo[1] === 1062) {
                return redirect()->back()->withInput()
                    ->with('error', 'يوجد سعر صرف مسجل بالفعل لنفس العملات في نفس التاريخ');
            }

            Log::error('ExchangeRate store failed', ['error' => $e->getMessage()]);
            return redirect()->back()->withInput()
                ->with('error', 'تعذر إتمام العملية. يرجى المحاولة لاحقاً.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ExchangeRate store failed', ['error' => $e->getMessage()]);
            return redirect()->back()->withInput()
                ->with('error', 'تعذر إتمام العملية. يرجى المحاولة لاحقاً.');
        }
    }

    public function edit(ExchangeRate $exchangeRate): View
    {
        $currencies = $this->getCurrencies();
        return view('admin.exchange-rates.edit', compact('exchangeRate', 'currencies'));
    }

    public function update(UpdateExchangeRateRequest $request, ExchangeRate $exchangeRate): RedirectResponse
    {
        $validated = $request->validated();
        $oldData = $exchangeRate->toArray();
        $oldFromCurrency = $exchangeRate->from_currency_id;
        $oldToCurrency = $exchangeRate->to_currency_id;

        try {
            DB::beginTransaction();

            $exchangeRate->update($validated);

            DB::afterCommit(function () use ($exchangeRate, $oldData, $oldFromCurrency, $oldToCurrency) {
                $this->clearRateCacheByIds($oldFromCurrency, $oldToCurrency);
                $this->clearRateCache($exchangeRate);

                $this->auditService->log(
                    auth()->id(),
                    'update',
                    'exchange_rates',
                    $exchangeRate->id,
                    $oldData,
                    $exchangeRate->fresh()->toArray()
                );
            });

            DB::commit();

            return redirect()->route('admin.exchange-rates.index')
                ->with('success', 'تم تحديث سعر الصرف بنجاح');
        } catch (QueryException $e) {
            DB::rollBack();
            if ($e->errorInfo[1] === 1062) {
                return redirect()->back()->withInput()
                    ->with('error', 'يوجد سعر صرف مسجل بالفعل لنفس العملات في نفس التاريخ');
            }
            Log::error('ExchangeRate update failed', ['error' => $e->getMessage()]);
            return redirect()->back()
                ->with('error', 'تعذر إتمام العملية. يرجى المحاولة لاحقاً.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ExchangeRate update failed', ['error' => $e->getMessage()]);
            return redirect()->back()
                ->with('error', 'تعذر إتمام العملية. يرجى المحاولة لاحقاً.');
        }
    }

    public function destroy(Request $request, ExchangeRate $exchangeRate)
    {
        $oldData = $exchangeRate->toArray();
        $fromCurrency = $exchangeRate->from_currency_id;
        $toCurrency = $exchangeRate->to_currency_id;
        $rateId = $exchangeRate->id;

        try {
            DB::beginTransaction();

            $exchangeRate->delete();

            DB::afterCommit(function () use ($rateId, $oldData, $fromCurrency, $toCurrency) {
                $this->clearRateCacheByIds($fromCurrency, $toCurrency);
                $this->auditService->log(
                    auth()->id(),
                    'delete',
                    'exchange_rates',
                    $rateId,
                    $oldData,
                    null
                );
            });

            DB::commit();

            return redirect()->route('admin.exchange-rates.index')
                ->with('success', 'تم حذف سعر الصرف بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ExchangeRate destroy failed', ['error' => $e->getMessage()]);
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'تعذر إتمام عملية الحذف. يرجى المحاولة لاحقاً.'], 422);
            }
            return redirect()->back()
                ->with('error', 'تعذر إتمام العملية. يرجى المحاولة لاحقاً.');
        }
    }

    private function getCurrencies()
    {
        return cache()->remember('currencies-list', 3600, function () {
            return Currency::orderBy('name')->get(['id', 'name', 'code', 'symbol']);
        });
    }
    */
}