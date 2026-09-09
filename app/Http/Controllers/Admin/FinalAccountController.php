<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\FinalAccount\StoreFinalAccountRequest;
use App\Http\Requests\Api\FinalAccount\UpdateFinalAccountRequest;
use App\Http\Resources\Admin\FinalAccountResource;
use App\Models\FinalAccount;
use App\Services\AuditService;
use App\Services\FinalAccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FinalAccountController extends Controller
{
    public function __construct(
        private FinalAccountService $finalAccountService,
        private AuditService $auditService,
    ) {
        $this->middleware('require.admin');
    }

    /**
     * عرض قائمة الحسابات الختامية.
     */
    public function index(): JsonResponse
    {
        $finalAccounts = $this->finalAccountService->getAll();

        return $this->success(
            FinalAccountResource::collection($finalAccounts),
            'تم جلب الحسابات الختامية بنجاح'
        );
    }

    /**
     * عرض حساب ختامي معين.
     */
    public function show(FinalAccount $finalAccount): JsonResponse
    {
        return $this->success(
            new FinalAccountResource($finalAccount->loadCount('accounts')),
            'تم جلب الحساب الختامي بنجاح'
        );
    }

    /**
     * إضافة حساب ختامي جديد.
     */
    public function store(StoreFinalAccountRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $finalAccount = $this->finalAccountService->create(
                $request->validated()
            );

            $finalAccountData = $finalAccount->toArray();
            $finalAccountId = $finalAccount->final_account_id;

            DB::commit();

            /*
            |--------------------------------------------------------------------------
            | Audit
            |--------------------------------------------------------------------------
            */

            $this->auditService->log(
                auth()->id(),
                'create',
                'final_accounts',
                $finalAccountId,
                null,
                $finalAccountData
            );

            return $this->success(
                new FinalAccountResource($finalAccount),
                'تم إضافة الحساب الختامي بنجاح'
            );
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Final account creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(
                'حدث خطأ أثناء إضافة الحساب الختامي. يرجى المحاولة مرة أخرى.',
                null,
                422
            );
        }
    }

    /**
     * تحديث اسم حساب ختامي.
     */
    public function update(UpdateFinalAccountRequest $request, FinalAccount $finalAccount): JsonResponse
    {
        try {
            DB::beginTransaction();

            $oldData = $finalAccount->toArray();

            $updated = $this->finalAccountService->update(
                $finalAccount,
                $request->validated()
            );

            $newData = $updated->toArray();

            $changes = array_diff_assoc($newData, $oldData);

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
                    'final_accounts',
                    $finalAccount->final_account_id,
                    array_intersect_key($oldData, $changes),
                    $changes
                );
            }

            return $this->success(
                new FinalAccountResource($updated),
                'تم تحديث الحساب الختامي بنجاح'
            );
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Final account update failed', [
                'error' => $e->getMessage(),
                'id' => $finalAccount->final_account_id,
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(
                'حدث خطأ أثناء تحديث الحساب الختامي. يرجى المحاولة مرة أخرى.',
                null,
                422
            );
        }
    }

    /**
     * حذف حساب ختامي.
     */
    public function destroy(FinalAccount $finalAccount): JsonResponse
    {
        /*
        |--------------------------------------------------------------------------
        | منع الحذف إذا كان الحساب الختامي مرتبطاً بحسابات
        |--------------------------------------------------------------------------
        */

        if ($finalAccount->accounts()->exists()) {
            return $this->error(
                'لا يمكن حذف الحساب الختامي لأنه مرتبط بحسابات',
                null,
                422
            );
        }

        try {
            DB::beginTransaction();

            $oldData = $finalAccount->toArray();
            $finalAccountId = $finalAccount->final_account_id;

            $this->finalAccountService->delete($finalAccount);

            DB::commit();

            /*
            |--------------------------------------------------------------------------
            | Audit
            |--------------------------------------------------------------------------
            */

            $this->auditService->log(
                auth()->id(),
                'delete',
                'final_accounts',
                $finalAccountId,
                $oldData,
                null
            );

            return $this->success(
                null,
                'تم حذف الحساب الختامي بنجاح'
            );
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Final account deletion failed', [
                'error' => $e->getMessage(),
                'id' => $finalAccountId,
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