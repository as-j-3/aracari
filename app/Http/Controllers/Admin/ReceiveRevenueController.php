<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReceiveRevenueMethod;
use App\Models\DigitalPocket;
use App\Models\ProviderService;
use App\Services\AuditService;
use App\Http\Requests\Web\ReceiveRevenue\StoreReceiveRevenueRequest;
use App\Http\Requests\Web\ReceiveRevenue\UpdateReceiveRevenueRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ReceiveRevenueController extends Controller
{
    public function __construct(
        private AuditService $auditService
    ) {
        $this->middleware('require.admin');
    }

    public function index(): View
    {
        $accounts = ReceiveRevenueMethod::with(['digitalPocket', 'provider'])
            ->latest()
            ->paginate(20);

        return view('admin.receive-revenue.index', compact('accounts'));
    }

    public function create(): View
    {
        $pockets = cache()->remember('digital-pockets-list', 3600, fn() => DigitalPocket::orderBy('pocket_name')->get());
        $providers = cache()->remember('provider-services-list', 3600, fn() => ProviderService::orderBy('name')->get());

        return view('admin.receive-revenue.create', compact('pockets', 'providers'));
    }


    public function store(StoreReceiveRevenueRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        DB::beginTransaction();
        try {
            $exists = ReceiveRevenueMethod::where('digital_pocket_id', $validated['digital_pocket_id'])
                ->where('provider_id', $validated['provider_id'])
                ->where('account_number', $validated['account_number'])
                ->exists();

            if ($exists) {
                DB::rollBack();
                return redirect()->back()->withInput()
                    ->with('error', 'يوجد حساب بنكي مسجل بالفعل بنفس البيانات');
            }

            $account = ReceiveRevenueMethod::create($validated);

            $accountId = $account->id;
            $accountData = $account->toArray();
            DB::afterCommit(function () use ($accountId, $accountData) {
                $this->auditService->log(
                    auth()->id(),
                    'created',
                    'receive_revenue_methods',
                    $accountId,
                    null,
                    $accountData
                );
            });

            DB::commit();

            return redirect()->route('admin.receive-revenue.index')
                ->with('success', 'تم إضافة الحساب البنكي بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ReceiveRevenue store failed: ' . $e->getMessage());
            return back()->withInput()
                ->with('error', 'حدث خطأ أثناء إضافة الحساب البنكي. يرجى المحاولة مرة أخرى.');
        }
    }

    public function edit(ReceiveRevenueMethod $receiveRevenue): View
    {
        $pockets = cache()->remember('digital-pockets-list', 3600, fn() => DigitalPocket::orderBy('pocket_name')->get());
        $providers = cache()->remember('provider-services-list', 3600, fn() => ProviderService::orderBy('name')->get());

        return view('admin.receive-revenue.edit', compact('receiveRevenue', 'pockets', 'providers'));
    }


    public function update(UpdateReceiveRevenueRequest $request, ReceiveRevenueMethod $receiveRevenue): RedirectResponse
    {
        $validated = $request->validated();

        DB::beginTransaction();
        try {
            $exists = ReceiveRevenueMethod::where('digital_pocket_id', $validated['digital_pocket_id'])
                ->where('provider_id', $validated['provider_id'])
                ->where('account_number', $validated['account_number'])
                ->where('id', '!=', $receiveRevenue->id)
                ->exists();

            if ($exists) {
                DB::rollBack();
                return redirect()->back()->withInput()
                    ->with('error', 'يوجد حساب بنكي مسجل بالفعل بنفس البيانات');
            }

            $oldData = $receiveRevenue->toArray();
            $receiveRevenue->update($validated);
            $newData = $receiveRevenue->fresh()->toArray();

            $changes = array_diff_assoc($newData, $oldData);
            if (!empty($changes)) {
                $receiveRevenueId = $receiveRevenue->id;
                $oldSubset = array_intersect_key($oldData, $changes);
                DB::afterCommit(function () use ($receiveRevenueId, $oldSubset, $changes) {
                    $this->auditService->log(
                        auth()->id(),
                        'updated',
                        'receive_revenue_methods',
                        $receiveRevenueId,
                        $oldSubset,
                        $changes
                    );
                });
            }

            DB::commit();

            return redirect()->route('admin.receive-revenue.index')
                ->with('success', 'تم تحديث الحساب البنكي بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ReceiveRevenue update failed: ' . $e->getMessage());
            return back()->withInput()
                ->with('error', 'حدث خطأ أثناء تحديث الحساب البنكي. يرجى المحاولة مرة أخرى.');
        }
    }

    public function destroy(ReceiveRevenueMethod $receiveRevenue): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $oldData = $receiveRevenue->toArray();
            $receiveRevenueId = $receiveRevenue->id;

            $receiveRevenue->delete();

            DB::afterCommit(function () use ($receiveRevenueId, $oldData) {
                $this->auditService->log(
                    auth()->id(),
                    'deleted',
                    'receive_revenue_methods',
                    $receiveRevenueId,
                    $oldData,
                    null
                );
            });

            DB::commit();

            return redirect()->route('admin.receive-revenue.index')
                ->with('success', 'تم حذف الحساب البنكي بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ReceiveRevenue destroy failed: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'لا يمكن حذف الحساب البنكي لارتباطه ببيانات أخرى.');
        }
    }
}
