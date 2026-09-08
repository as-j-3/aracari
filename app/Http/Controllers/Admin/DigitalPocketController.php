<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DigitalPocket;
use App\Models\Currency;
use App\Http\Requests\Web\DigitalPocket\StoreDigitalPocketRequest;
use App\Http\Requests\Web\DigitalPocket\UpdateDigitalPocketRequest;
use App\Http\Requests\Web\DigitalPocket\IndexDigitalPocketRequest;
use App\Http\Resources\Admin\DigitalPocketResource;
use App\Repositories\Contracts\DigitalPocketRepositoryInterface;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Builder;

class DigitalPocketController extends Controller
{
    public function __construct(
        private AuditService $auditService,
        private DigitalPocketRepositoryInterface $digitalPocketRepository
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

    public function index(IndexDigitalPocketRequest $request): JsonResponse
    {
        $pockets = $this->digitalPocketRepository->search(
            $request->validated(),
            $request->validated('per_page') ?? 20
        );

        return $this->success(
            DigitalPocketResource::collection($pockets),
            'تم استرجاع المحافظ الرقمية بنجاح'
        );
    }

    public function store(StoreDigitalPocketRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            DB::beginTransaction();

            $pocket = $this->digitalPocketRepository->create($validated);

            DB::afterCommit(function () use ($pocket) {
                $this->auditService->log(
                    auth()->id(),
                    'create',
                    'digital_pockets',
                    $pocket->id,
                    null,
                    $pocket->fresh()->toArray()
                );

                cache()->forget('digital-pockets-list');
            });

            DB::commit();

            return $this->success(
                new DigitalPocketResource($pocket->fresh(['currency'])),
                'تم إضافة المحفظة الرقمية بنجاح'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('DigitalPocket store failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(
                'تعذر إتمام العملية. يرجى المحاولة مرة أخرى.',
                null,
                422
            );
        }
    }

    public function update(UpdateDigitalPocketRequest $request, int $digitalPocket): JsonResponse
    {
        $pocket = $this->digitalPocketRepository->findById($digitalPocket);

        if (! $pocket) {
            return $this->error(
                'المحفظة الرقمية غير موجودة',
                null,
                404
            );
        }

        $validated = $request->validated();
        $old = $pocket->toArray();

        try {
            DB::beginTransaction();

            $this->digitalPocketRepository->update($pocket->id, $validated);

            DB::afterCommit(function () use ($pocket, $old) {
                $this->auditService->log(
                    auth()->id(),
                    'update',
                    'digital_pockets',
                    $pocket->id,
                    $old,
                    $pocket->fresh()->toArray()
                );

                cache()->forget('digital-pockets-list');
            });

            DB::commit();

            return $this->success(
                new DigitalPocketResource($pocket->fresh(['currency'])),
                'تم تحديث المحفظة بنجاح'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('DigitalPocket update failed', [
                'error' => $e->getMessage(),
                'id'    => $pocket->id,
            ]);

            return $this->error(
                'تعذر إتمام العملية. يرجى المحاولة مرة أخرى.',
                null,
                422
            );
        }
    }

    public function destroy(int $digitalPocket): JsonResponse
    {
        $pocket = $this->digitalPocketRepository->findById($digitalPocket);

        if (! $pocket) {
            return $this->error(
                'المحفظة الرقمية غير موجودة',
                null,
                404
            );
        }

        // فحص الارتباطات
        if ($this->digitalPocketRepository->hasReceiveRevenueMethods($pocket->id)) {
            return $this->error(
                'لا يمكن حذف المحفظة لارتباطها بحسابات بنكية',
                null,
                422
            );
        }

        $pocketId = $pocket->id;
        $old = $pocket->toArray();

        try {
            DB::beginTransaction();

            $this->digitalPocketRepository->delete($pocketId);

            DB::afterCommit(function () use ($pocketId, $old) {
                $this->auditService->log(
                    auth()->id(),
                    'delete',
                    'digital_pockets',
                    $pocketId,
                    $old,
                    null
                );

                cache()->forget('digital-pockets-list');
            });

            DB::commit();

            return $this->success(
                null,
                'تم حذف المحفظة بنجاح'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('DigitalPocket destroy failed', [
                'error' => $e->getMessage(),
                'id'    => $pocketId,
            ]);

            return $this->error(
                'تعذر إتمام العملية. يرجى المحاولة مرة أخرى.',
                null,
                422
            );
        }
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
    public function index(IndexDigitalPocketRequest $request): View
    {
        $query = DigitalPocket::with('currency');

        if ($search = $request->get('search')) {
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search);
            $query->where(fn (Builder $q) => $q->where('pocket_name', 'like', "%{$escaped}%"));
        }

        if ($currencyId = $request->get('currency_id')) {
            $query->where('currency_id', $currencyId);
        }

        $pockets = $query->latest('created_at')
            ->paginate($request->get('per_page', 20))
            ->withQueryString();

        $currencies = $this->getCurrencies();

        return view('admin.digital-pockets.index', compact('pockets', 'currencies'));
    }

    public function create(): View
    {
        $currencies = $this->getCurrencies();
        return view('admin.digital-pockets.create', compact('currencies'));
    }

    public function store(StoreDigitalPocketRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        try {
            DB::beginTransaction();

            $pocket = DigitalPocket::create($validated);

            DB::afterCommit(function () use ($pocket) {
                $this->auditService->log(
                    auth()->id(),
                    'create',
                    'digital_pockets',
                    $pocket->id,
                    null,
                    $pocket->fresh()->toArray()
                );

                cache()->forget('digital-pockets-list');
            });

            DB::commit();

            return redirect()->route('admin.digital-pockets.index')
                ->with('success', 'تم إضافة المحفظة الرقمية بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('DigitalPocket store failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->back()->withInput()
                ->with('error', 'تعذر إتمام العملية. يرجى المحاولة مرة أخرى.');
        }
    }

    public function edit(DigitalPocket $digitalPocket): View
    {
        $currencies = $this->getCurrencies();
        return view('admin.digital-pockets.edit', compact('digitalPocket', 'currencies'));
    }

    public function update(UpdateDigitalPocketRequest $request, DigitalPocket $digitalPocket): RedirectResponse
    {
        $validated = $request->validated();
        $old = $digitalPocket->toArray();

        try {
            DB::beginTransaction();

            $digitalPocket->update($validated);

            DB::afterCommit(function () use ($digitalPocket, $old) {
                $this->auditService->log(
                    auth()->id(),
                    'update',
                    'digital_pockets',
                    $digitalPocket->id,
                    $old,
                    $digitalPocket->fresh()->toArray()
                );

                cache()->forget('digital-pockets-list');
            });

            DB::commit();

            return redirect()->route('admin.digital-pockets.index')
                ->with('success', 'تم تحديث المحفظة بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('DigitalPocket update failed', [
                'error' => $e->getMessage(),
                'id'    => $digitalPocket->id,
            ]);
            return redirect()->back()
                ->with('error', 'تعذر إتمام العملية. يرجى المحاولة مرة أخرى.');
        }
    }

    public function destroy(Request $request, DigitalPocket $digitalPocket): RedirectResponse|JsonResponse
    {
        // فحص الارتباطات
        if ($digitalPocket->receiveRevenueMethods()->exists()) {
            if ($request->ajax()) {
                return response()->json([
                    'message' => 'لا يمكن حذف المحفظة لارتباطها بحسابات بنكية',
                ], 422);
            }
            return redirect()->back()
                ->with('error', 'لا يمكن حذف المحفظة لارتباطها بحسابات بنكية');
        }
        $pocketId = $digitalPocket->id;
        $old = $digitalPocket->toArray();

        try {
            DB::beginTransaction();

            $digitalPocket->delete();

            DB::afterCommit(function () use ($pocketId, $old) {
                $this->auditService->log(
                    auth()->id(),
                    'delete',
                    'digital_pockets',
                    $pocketId,
                    $old,
                    null
                );

                cache()->forget('digital-pockets-list');
            });

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'message' => 'تم حذف المحفظة بنجاح',
                ]);
            }

            return redirect()->route('admin.digital-pockets.index')
                ->with('success', 'تم حذف المحفظة بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('DigitalPocket destroy failed', [
                'error' => $e->getMessage(),
                'id'    => $pocketId,
            ]);
            if ($request->ajax()) {
                return response()->json([
                    'message' => 'تعذر إتمام العملية. يرجى المحاولة مرة أخرى.',
                ], 500);
            }
            return redirect()->back()
                ->with('error', 'تعذر إتمام العملية. يرجى المحاولة مرة أخرى.');
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