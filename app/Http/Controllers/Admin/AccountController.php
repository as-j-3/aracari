<?php

namespace App\Http\Controllers\Admin;

use App\Constants\AccountCodes;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Account\StoreAccountRequest;
use App\Http\Requests\Api\Account\UpdateAccountRequest;
use App\Http\Resources\Admin\AccountResource;
use App\Models\Account;
use App\Services\AccountService;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AccountController extends Controller
{
    public function __construct(
        private AccountService $accountService,
        private AuditService $auditService
    ) {
        $this->middleware('require.admin');
    }

    /**
     * عرض قائمة الحسابات مع إمكانية البحث.
     */
    public function index(Request $request): JsonResponse
    {
        /*
        |--------------------------------------------------------------------------
        | Old Web implementation - kept for reference
        |--------------------------------------------------------------------------
        $accounts = $this->accountService->getAccounts([
            'search' => $request->search,
        ]);

        return view('admin.accounts.index', compact('accounts'));
        */

        $accounts = $this->accountService->getAccounts([
            'search' => $request->search,
        ]);

        $accounts->getCollection()->load(['parent', 'children']);

        return $this->success([
            'accounts' => AccountResource::collection($accounts->items()),
            'pagination' => [
                'current_page' => $accounts->currentPage(),
                'last_page' => $accounts->lastPage(),
                'per_page' => $accounts->perPage(),
                'total' => $accounts->total(),
            ],
        ], 'تم جلب الحسابات بنجاح');
    }

    /**
     * عرض الحسابات الأب المتاحة لإنشاء حساب جديد.
     */
   public function parents(): JsonResponse
    {
        /*
        |--------------------------------------------------------------------------
        | Old Web implementation - kept for reference
        |--------------------------------------------------------------------------
        return view('admin.accounts.create', [
            'parentAccounts' => $this->accountService->getParentAccounts(),
        ]);
        */

        $parentAccounts = $this->accountService->getParentAccounts();

        return $this->success([
            'parent_accounts' => $parentAccounts->map(function ($account) {
                return [
                    'id'             => $account->id,
                    'account_code'   => $account->account_code,
                    'account_name'   => $account->account_name,
                    'account_nature' => $account->account_nature,
                    'type_account'   => (int) $account->type_account, // 0: Main (رئيسي)
                ];
            }),
        ], 'تم جلب الحسابات الأب بنجاح');
    }

    /**
     * إنشاء حساب جديد.
     */
    public function store(StoreAccountRequest $request): JsonResponse
    {
        try {
            // account_code is generated automatically by the service from the
            // selected parent; the client never supplies it.
            $result = $this->accountService->createAccount($request->validated(), auth()->id());

            if (! $result['success']) {
                /*
                |--------------------------------------------------------------------------
                | Old Web implementation - kept for reference
                |--------------------------------------------------------------------------
                return back()->withInput()->with('error', $result['message']);
                */
                return $this->error($result['message'], null, 422);
            }

            return $this->success(
                new AccountResource($result['data']),
                $result['message']
            );
        } catch (\Exception $e) {
            Log::error('Account store failed: '.$e->getMessage());

            return $this->error('حدث خطأ أثناء حفظ الحساب. يرجى المحاولة مرة أخرى.', null, 422);
        }
    }

    /**
     * عرض بيانات حساب معين والحسابات الأب المتاحة للتعديل.
     */
    public function edit(Account $account): JsonResponse
    {
        $parentAccounts = $this->accountService->getParentAccounts()
            ->where('id', '!=', $account->id);

        /*
        |--------------------------------------------------------------------------
        | Old Web implementation - kept for reference
        |--------------------------------------------------------------------------
        return view('admin.accounts.edit', [
            'account' => $account,
            'parentAccounts' => $this->accountService->getParentAccounts()
                ->where('id', '!=', $account->id),
        ]);
        */

        return $this->success([
            'account' => new AccountResource($account->load(['parent', 'children'])),
            'parent_accounts' => AccountResource::collection($parentAccounts),
        ], 'تم جلب بيانات الحساب بنجاح');
    }

    /**
     * تحديث اسم الحساب.
     */
    public function update(UpdateAccountRequest $request, Account $account): JsonResponse
    {
        if ($response = $this->ensureNotProtected($account)) {
            return $response;
        }

        if ($request->has('parent_id') && $request->parent_id != $account->parent_id) {
            /*
            |--------------------------------------------------------------------------
            | Old Web implementation - kept for reference
            |--------------------------------------------------------------------------
            return back()->withInput()
                ->with('error', 'لا يمكن تغيير الحساب الأب لأنه قد تم استخدامه في معاملات محاسبية.');
            */
            return $this->error('لا يمكن تغيير الحساب الأب لأنه قد تم استخدامه في معاملات محاسبية.', null, 422);
        }

        try {
            $result = $this->accountService->updateAccount($account, [
                'account_name' => $request->validated('account_name'),
            ]);

            if (! $result['success']) {
                return $this->error($result['message'], null, 422);
            }

            return $this->success(
                new AccountResource($result['data']->load(['parent', 'children'])),
                $result['message']
            );
        } catch (\Exception $e) {
            Log::error('Account update failed: '.$e->getMessage());

            return $this->error('حدث خطأ أثناء تحديث الحساب. يرجى المحاولة مرة أخرى.', null, 422);
        }
    }

    /**
     * حذف حساب.
     */
    public function destroy(Account $account): JsonResponse
    {
        if ($response = $this->ensureNotProtected($account)) {
            return $response;
        }

        try {
            $result = $this->accountService->deleteAccount($account);

            if (! $result['success']) {
                /*
                |--------------------------------------------------------------------------
                | Old Web implementation - kept for reference
                |--------------------------------------------------------------------------
                return back()->with('error', $result['message']);
                */
                return $this->error($result['message'], null, 422);
            }

            return $this->success(null, $result['message']);
        } catch (\Exception $e) {
            Log::error('Account destroy failed: '.$e->getMessage());

            return $this->error('تعذر إتمام عملية الحذف. يرجى المحاولة لاحقاً.', null, 422);
        }
    }

    /**
     * منع تعديل/حذف الحسابات النظامية الجذرية.
     */
    private function ensureNotProtected(Account $account): ?JsonResponse
    {
        $protectedCodes = [
            AccountCodes::ASSETS,
            AccountCodes::LIABILITIES,
            AccountCodes::REVENUE,
            AccountCodes::EXPENSES,
        ];

        if (in_array($account->account_code, $protectedCodes)) {
            /*
            |--------------------------------------------------------------------------
            | Old Web implementation - kept for reference
            |--------------------------------------------------------------------------
            abort(403, 'لا يمكن تعديل أو حذف حساب نظامي جذري.');
            */
            return $this->error('لا يمكن تعديل أو حذف حساب نظامي جذري.', null, 403);
        }

        return null;
    }
}
