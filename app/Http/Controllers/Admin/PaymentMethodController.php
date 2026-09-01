<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use App\Http\Requests\Web\PaymentMethod\StorePaymentMethodRequest;
use App\Http\Requests\Web\PaymentMethod\UpdatePaymentMethodRequest;
use App\Http\Requests\Web\Lookup\IndexLookupRequest;
use App\Services\AuditService;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Builder;
use App\Constants\PaymentMethod as PaymentMethodConstants;
class PaymentMethodController extends Controller
{
    protected AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
        $this->middleware('require.admin');
    }

    public function index(IndexLookupRequest $request): View
    {
        $query = PaymentMethod::query();

        if ($search = $request->get('search')) {
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search);
            $query->where(fn (Builder $q) => $q->where('method_name', 'like', "%{$escaped}%"));
        }

        $methods = $query->latest('created_at')
            ->paginate($request->get('per_page', 15))
            ->withQueryString();

        return view('admin.payment-methods.index', compact('methods'));
    }

    public function create(): View
    {
        return view('admin.payment-methods.create');
    }

    public function store(StorePaymentMethodRequest $request): RedirectResponse
    {
        try {
            DB::beginTransaction();

            $method = PaymentMethod::create($request->validated());

            DB::afterCommit(function () use ($method) {
                $this->auditService->log(
                    auth()->id(),
                    'create',
                    'payment_methods',
                    $method->id,
                    null,
                    $method->fresh()->toArray()
                );

                cache()->forget('payment-methods-list');
            });

            DB::commit();

            return redirect()->route('admin.lookups.payment-methods.index')
                ->with('success', 'تم إضافة وسيلة الدفع بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PaymentMethod store failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->withInput()
                ->with('error', 'حدث خطأ أثناء إضافة وسيلة الدفع. يرجى المحاولة مرة أخرى.');
        }
    }

    public function edit(int $id): View
    {
        $method = PaymentMethod::findOrFail($id);
        return view('admin.payment-methods.edit', compact('method'));
    }

    public function update(UpdatePaymentMethodRequest $request, int $id): RedirectResponse
    {
        $method = PaymentMethod::findOrFail($id);

        try {
            DB::beginTransaction();

            $oldValues = $method->toArray();
            $method->update($request->validated());

            DB::afterCommit(function () use ($method, $oldValues) {
                $this->auditService->log(
                    auth()->id(),
                    'update',
                    'payment_methods',
                    $method->id,
                    $oldValues,
                    $method->fresh()->toArray()
                );

                cache()->forget('payment-methods-list');
            });

            DB::commit();

            return redirect()->route('admin.lookups.payment-methods.index')
                ->with('success', 'تم تحديث وسيلة الدفع بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PaymentMethod update failed', [
                'error' => $e->getMessage(),
                'id'    => $method->id,
            ]);
            return back()->withInput()
                ->with('error', 'حدث خطأ أثناء تحديث وسيلة الدفع. يرجى المحاولة مرة أخرى.');
        }
    }

    public function destroy(int $id): RedirectResponse
    {
        $method = PaymentMethod::findOrFail($id);

        if (in_array($id, [
            PaymentMethodConstants::WALLET,
            PaymentMethodConstants::CREDIT_CARD,
            PaymentMethodConstants::BANK_TRANSFER,
            PaymentMethodConstants::CASH,
        ])) {
            return back()->with('error', 'لا يمكن حذف وسيلة دفع نظامية. يمكنك تعطيلها بدلاً من ذلك.');
        }

        if ($method->bookingPayments()->exists()) {
            return redirect()->back()
                ->with('error', 'لا يمكن حذف وسيلة الدفع لوجود حجوزات مرتبطة بها');
        }

        try {
            DB::beginTransaction();

            $recordId = $method->id;
            $oldValues = $method->toArray();
            $method->delete();

            DB::afterCommit(function () use ($recordId, $oldValues) {
                $this->auditService->log(
                    auth()->id(),
                    'delete',
                    'payment_methods',
                    $recordId,
                    $oldValues,
                    null
                );

                cache()->forget('payment-methods-list');
            });

            DB::commit();

            return redirect()->route('admin.lookups.payment-methods.index')
                ->with('success', 'تم حذف وسيلة الدفع بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PaymentMethod destroy failed', [
                'error' => $e->getMessage(),
                'id'    => $method->id,
            ]);
            return back()
                ->with('error', 'تعذر إتمام عملية الحذف. يرجى المحاولة لاحقاً.');
        }
    }
}