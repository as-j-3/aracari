<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service for Account operations
 * 
 * Handles business logic for account management
 * Follows SOLID principles with clean, maintainable code
 */
class AccountService
{
    public function __construct(
        private AuditService $auditService
    ) {}
    /**
     * Create new account with automatic level calculation
     * 
     * This method creates a new account with automatic level calculation
     * based on parent account hierarchy.
     * 
     * Business logic:
     * - Validates account data
     * - Calculates account level based on parent
     * - Creates account with creator ID
     * 
     * @param array $data Account data
     * @param int $creatorId User ID of the creator
     * @return array Creation result with success status and message
     */
    public function createAccount(array $data, int $creatorId): array
    {
        try {
            DB::beginTransaction();

            // Calculate account level based on parent
            $level = 1;
            if (!empty($data['parent_id'])) {
                $parent = Account::find($data['parent_id']);
                if ($parent) {
                    $level = $parent->level + 1;
                }
            }

            $account = Account::create([
                'account_name' => $data['account_name'],
                'account_code' => $data['account_code'],
                'account_type' => $data['account_type'],
                'parent_id' => $data['parent_id'] ?? null,
                'level' => $level,
                'created_by_user' => $creatorId,
            ]);

            DB::afterCommit(function () use ($account, $creatorId) {
                $this->auditService->log($creatorId, 'create', 'accounts', $account->id, null, $account->toArray());
            });

            DB::commit();

            return [
                'success' => true,
                'message' => 'تم إنشاء الحساب بنجاح',
                'data' => $account,
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to create account: ' . $e->getMessage(), [
                'account_data' => $data,
                'creator_id' => $creatorId,
            ]);

            return [
                'success' => false,
                'message' => 'فشل إنشاء الحساب: ',
            ];
        }
    }

    /**
     * Update account information
     * 
     * This method updates account information with automatic level
     * recalculation if parent changes.
     * 
     * @param Account $account Account to update
     * @param array $data Updated account data
     * @return array Update result with success status and message
     */
    public function updateAccount(Account $account, array $data): array
    {
        //  منع تغيير account_code (يكسر المعاملات التاريخية)
        if (isset($data['account_code']) && $data['account_code'] !== $account->account_code) {
            return [
                'success' => false,
                'message' => 'لا يمكن تغيير كود الحساب بعد إنشائه. هذا سيؤثر على المعاملات التاريخية.',
            ];
        }

        try {
            DB::beginTransaction();

            $oldData = $account->toArray();

            $updateData = [
                'account_name' => $data['account_name'] ?? $account->account_name,
                'account_type' => $data['account_type'] ?? $account->account_type,
            ];

            // Recalculate level if parent changes
            if (isset($data['parent_id']) && $data['parent_id'] != $account->parent_id) {
                if ($data['parent_id'] == $account->id) {
                    return [
                        'success' => false,
                        'message' => 'لا يمكن أن يكون الحساب أب لنفسه',
                    ];
                }

                $updateData['parent_id'] = $data['parent_id'];

                if (!empty($data['parent_id'])) {
                    $parent = Account::find($data['parent_id']);
                    if ($parent) {
                        $updateData['level'] = $parent->level + 1;
                    } else {
                        $updateData['level'] = 1;
                    }
                } else {
                    $updateData['level'] = 1;
                }
            }

            $account->update($updateData);

            //  Audit في afterCommit
            DB::afterCommit(function () use ($account, $oldData) {
                $this->auditService->log(
                    auth()->id(),
                    'update',
                    'accounts',
                    $account->id,
                    $oldData,
                    $account->fresh()->toArray()
                );
            });

            DB::commit();

            return [
                'success' => true,
                'message' => 'تم تحديث الحساب بنجاح',
                'data' => $account->fresh(),
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update account: ' . $e->getMessage(), [
                'account_id' => $account->id,
                'account_data' => $data,
            ]);

            return [
                'success' => false,
                'message' => 'فشل تحديث الحساب',
            ];
        }
    }

    /**
     * Delete account
     * 
     * This method deletes an account with validation to prevent
     * deletion of accounts with child accounts.
     * 
     * @param Account $account Account to delete
     * @return array Deletion result with success status and message
     */
    public function deleteAccount(Account $account): array
    {
        // فحوصات قبل transaction
        if ($account->children()->count() > 0) {
            return [
                'success' => false,
                'message' => 'لا يمكن حذف الحساب لأنه يحتوي على حسابات فرعية',
            ];
        }

        if (Transaction::where('account_code', $account->account_code)->exists()) {
            return [
                'success' => false,
                'message' => 'لا يمكن حذف الحساب لأنه مستخدم في قيود محاسبية سابقة',
            ];
        }

        try {
            DB::beginTransaction();

            $oldData = $account->toArray();
            $accountId = $account->id;

            $account->delete();

            //  Audit في afterCommit
            DB::afterCommit(function () use ($accountId, $oldData) {
                $this->auditService->log(
                    auth()->id(),
                    'delete',
                    'accounts',
                    $accountId,
                    $oldData,
                    null
                );
            });

            DB::commit();

            return [
                'success' => true,
                'message' => 'تم حذف الحساب بنجاح',
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to delete account: ' . $e->getMessage(), [
                'account_id' => $account->id,
            ]);

            return [
                'success' => false,
                'message' => 'فشل حذف الحساب',
            ];
        }
    }

    /**
     * Get accounts with filtering
     * 
     * @param array $filters Filter criteria
     * @param int $perPage Items per page
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getAccounts(array $filters = [], int $perPage = 20)
    {
        $query = Account::query();

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $escapedSearch = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $filters['search']);
            $q->where('account_name', 'like', '%' . $escapedSearch . '%')
                  ->orWhere('account_code', 'like', '%' . $escapedSearch . '%');
            });
        }

        return $query->orderBy('account_code')->paginate($perPage);
    }

    /**
     * Get parent accounts (root level)
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getParentAccounts()
    {
        return Account::whereNull('parent_id')->get();
    }
}
