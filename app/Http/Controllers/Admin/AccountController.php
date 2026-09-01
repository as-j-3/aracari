<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Services\AccountService;
use App\Services\AuditService;
use App\Constants\AccountCodes; 
use App\Constants\AccountTypes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AccountController extends Controller
{
    public function __construct(
        private AccountService $accountService,
        private AuditService $auditService
    ) {
        $this->middleware('require.admin');
    }

    public function index(Request $request): View
    {
        $accounts = $this->accountService->getAccounts([
            'search' => $request->search,
        ]);

        return view('admin.accounts.index', compact('accounts'));
    }

    public function create(): View
    {
        return view('admin.accounts.create', [
            'parentAccounts' => $this->accountService->getParentAccounts(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        try {
            $validated = $request->validate([
                'account_name' => 'required|string|max:255',
                'account_code' => 'required|string|max:50|unique:accounts,account_code',
                'account_type' => 'required|in:' . implode(',', AccountTypes::all()),
                'parent_id' => 'nullable|exists:accounts,id',
            ]);

            $result = $this->accountService->createAccount($validated, auth()->id());

            if (!$result['success']) {
                return back()->withInput()->with('error', $result['message']);
            }

            return redirect()->route('admin.accounting.accounts.index')
                ->with('success', $result['message']);
        } catch (\Exception $e) {
            Log::error('Account store failed: ' . $e->getMessage());
            return back()->withInput()->with('error', 'حدث خطأ أثناء حفظ الحساب. يرجى المحاولة مرة أخرى.');
        }
    }

    public function edit(Account $account): View
    {
        return view('admin.accounts.edit', [
            'account' => $account,
            'parentAccounts' => $this->accountService->getParentAccounts()
                ->where('id', '!=', $account->id),
        ]);
    }

    public function update(Request $request, Account $account): RedirectResponse
    {
        $this->ensureNotProtected($account);

        try {
            $validated = $request->validate([
                'account_name' => 'required|string|max:255',
            ]);

            if ($request->has('parent_id') && $request->parent_id != $account->parent_id) {
                return back()->withInput()
                    ->with('error', 'لا يمكن تغيير الحساب الأب لأنه قد تم استخدامه في معاملات محاسبية.');
            }

            DB::beginTransaction();
            $oldData = $account->toArray();
            $account->update(['account_name' => $validated['account_name']]);
            $newData = $account->fresh()->toArray();

            $accountId = $account->id;
            DB::afterCommit(function () use ($accountId, $oldData, $newData) {
                $this->auditService->log(
                    auth()->id(),
                    'update',
                    'accounts',
                    $accountId,
                    $oldData,
                    $newData
                );
            });

            DB::commit();

            return redirect()->route('admin.accounting.accounts.index')
                ->with('success', 'تم تحديث الحساب بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Account update failed: ' . $e->getMessage());
            return back()->withInput()->with('error', 'حدث خطأ أثناء تحديث الحساب. يرجى المحاولة مرة أخرى.');
        }
    }

    public function destroy(Account $account): RedirectResponse
    {
        $this->ensureNotProtected($account);
        
        try {
            $result = $this->accountService->deleteAccount($account);

            if (!$result['success']) {
                return back()->with('error', $result['message']);
            }

            return redirect()->route('admin.accounting.accounts.index')
                ->with('success', $result['message']);
        } catch (\Exception $e) {
            Log::error('Account destroy failed: ' . $e->getMessage());
            return back()->with('error', 'تعذر إتمام عملية الحذف. يرجى المحاولة لاحقاً.');
        }
    }

    private function ensureNotProtected(Account $account): void
    {
        $protectedCodes = [
            AccountCodes::ASSETS,
            AccountCodes::LIABILITIES,
            AccountCodes::REVENUE,
            AccountCodes::EXPENSES
        ];

        if (in_array($account->account_code, $protectedCodes)) {
            abort(403, 'لا يمكن تعديل أو حذف حساب نظامي جذري.');
        }
    }
}