<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
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
     * إنشاء حساب جديد بآلية توليد تلقائي للكود والنوع والطبيعة.
     *
     * المدخلات من العميل:
     *   - account_name         : اسم الحساب (إلزامي)
     *   - parent_account_code  : كود الحساب الأب (اختياري)
     *   - final_account_id     : الحساب الختامي (اختياري — يُستنتج من الأب إن لم يُرسَل)
     *
     * المعالجة داخل السيرفس:
     *   1. البحث عن الحساب الأب عبر parent_account_code واستخراج parent_id
     *   2. تحديد normal_account (main/sub) بناءً على وجود أب أم لا
     *   3. توليد account_code تلقائياً (lockForUpdate لمنع التضارب)
     *   4. وراثة account_type و account_nature من الحساب الأب
     *   5. ربط final_account_id من الأب إن لم يُرسَل صراحةً
     *
     * @param  array  $data  البيانات المُحققة من StoreAccountRequest
     * @param  int  $creatorId  معرف المستخدم المنشئ
     * @return array{success: bool, message: string, data?: Account}
     */
    public function createAccount(array $data, int $creatorId): array
    {
        try {
            DB::beginTransaction();

            $parent = null;

            // ── 1. تحديد الحساب الأب ──────────────────────────────────────────
            if (! empty($data['parent_account_code'])) {
                $parent = Account::where('account_code', $data['parent_account_code'])
                    ->lockForUpdate()
                    ->first();

                if (! $parent) {
                    DB::rollBack();

                    return [
                        'success' => false,
                        'message' => 'الحساب الأب غير موجود.',
                    ];
                }
            }

            // ── 2. normal_account و account_nature — من العميل مباشرةً ──────────────────
            $normalAccount = $data['normal_account'];
            $accountNature = $data['account_nature'];

            // ── 3. توليد account_code تلقائياً (مع قفل لمنع التضارب) ────────────
            if ($parent) {
                $accountCode = $this->generateAccountCode($parent, $normalAccount);
            } else {
                // حساب جذري — نولد كوداً جذرياً تسلسلياً
                $accountCode = $this->generateRootAccountCode();
            }

            // ── 4. account_type — يُورث من الأب ───────────────────────────────
            if ($parent) {
                $accountType = $parent->account_type;
            } else {
                $accountType = $data['account_type'] ?? null;
                if (! $accountType) {
                    DB::rollBack();

                    return [
                        'success' => false,
                        'message' => 'الحسابات الجذرية تتطلب تحديد نوع الحساب.',
                    ];
                }
            }

            // ── 5. تحديد final_account_id ─────────────────────────────────────
            //    الأولوية: ما أرسله العميل ← ما ورثه من الأب ← null
            $finalAccountId = $data['final_account_id']
                ?? $parent?->final_account_id
                ?? null;

            // ── 6. إنشاء الحساب ───────────────────────────────────────────────
            $account = Account::create([
                'account_name' => $data['account_name'],
                'account_code' => $accountCode,
                'account_type' => $accountType,
                'account_nature' => $accountNature,
                'normal_account' => $normalAccount,
                'parent_id' => $parent?->id,
                'final_account_id' => $finalAccountId,
                'created_by_user' => $creatorId,
            ]);

            DB::afterCommit(function () use ($account, $creatorId) {
                $this->auditService->log(
                    $creatorId,
                    'create',
                    'accounts',
                    $account->id,
                    null,
                    $account->toArray()
                );
            });

            DB::commit();

            return [
                'success' => true,
                'message' => 'تم إنشاء الحساب بنجاح',
                'data' => $account->load(['parent', 'finalAccount']),
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to create account: '.$e->getMessage(), [
                'account_data' => $data,
                'creator_id' => $creatorId,
            ]);

            return [
                'success' => false,
                'message' => 'فشل إنشاء الحساب: '.$e->getMessage(),
            ];
        }
    }

    /**
     * تحديث بيانات الحساب (الاسم فقط — الكود محمي).
     *
     * @return array{success: bool, message: string, data?: Account}
     */
    public function updateAccount(Account $account, array $data): array
    {
        // منع تغيير account_code (يكسر المعاملات التاريخية)
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
            ];

            $account->update($updateData);

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
                'data' => $account->fresh()->load(['parent', 'children', 'finalAccount']),
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update account: '.$e->getMessage(), [
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
     * حذف الحساب مع التحقق من عدم وجود فروع أو معاملات مرتبطة.
     *
     * @return array{success: bool, message: string}
     */
    public function deleteAccount(Account $account): array
    {
        if ($account->children()->count() > 0) {
            return [
                'success' => false,
                'message' => 'لا يمكن حذف الحساب لأنه يحتوي على حسابات فرعية',
            ];
        }

        if (Transaction::where('account_id', $account->id)->exists()) {
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

            Log::error('Failed to delete account: '.$e->getMessage(), [
                'account_id' => $account->id,
            ]);

            return [
                'success' => false,
                'message' => 'فشل حذف الحساب',
            ];
        }
    }

    /**
     * جلب الحسابات مع دعم البحث والترقيم.
     */
    public function getAccounts(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Account::query();

        if (! empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $filters['search']);
                $q->where('account_name', 'like', '%'.$escaped.'%')
                    ->orWhere('account_code', 'like', '%'.$escaped.'%');
            });
        }

        if (! empty($filters['account_type'])) {
            $query->where('account_type', $filters['account_type']);
        }

        if (! empty($filters['normal_account'])) {
            $query->where('normal_account', $filters['normal_account']);
        }

        return $query->with(['parent', 'finalAccount'])
            ->orderBy('account_code')
            ->paginate($perPage);
    }

    /**
     * جلب الحسابات الجذرية (بدون أب).
     */
    public function getParentAccounts(): Collection
    {
        return Account::where('normal_account', 'main')
            ->orderBy('account_code')
            ->get();
    }

    // ══════════════════════════════════════════════════════════════════
    // Helper Methods (Private)
    // ══════════════════════════════════════════════════════════════════

    /**
     * توليد كود الحساب بناءً على الأب ونوعه (main/sub).
     *
     * نمط الترقيم:
     *   - main  : parentCode + رقم تسلسلي (مثال: "1" → أول فرع رئيسي "11")
     *   - sub   : parentCode + "0001" → تسلسلي (مثال: "11" → "110001", "110002" ...)
     */
    private function generateAccountCode(Account $parent, string $normalAccount): string
    {
        // قفل صف الأب لمنع race condition
        Account::where('id', $parent->id)->lockForUpdate()->first();

        if ($normalAccount === 'main') {
            return $this->generateMainAccountCode($parent);
        }

        return $this->generateSubAccountCode($parent);
    }

    /**
     * توليد كود للحساب الرئيسي الفرعي (main تحت أب).
     * النمط: parentCode + رقم تسلسلي (1، 2، 3...)
     * مثال: الأب "1" → "11", "12", "13"
     *        الأب "11" → "111", "112"
     */
    private function generateMainAccountCode(Account $parent): string
    {
        $lastCode = Account::where('parent_id', $parent->id)
            ->where('normal_account', 'main')
            ->lockForUpdate()
            ->max('account_code');

        if (! $lastCode) {
            // أول فرع رئيسي: parentCode + "1"
            return $parent->account_code.'1';
        }

        // الفرع التالي: آخر كود + 1
        return (string) ((int) $lastCode + 1);
    }

    /**
     * توليد كود للحساب الفرعي التشغيلي (sub).
     * النمط: parentCode + "0001" تسلسلي
     * مثال: الأب "11" → "110001", "110002", "110003"
     */
    private function generateSubAccountCode(Account $parent): string
    {
        $lastCode = Account::where('parent_id', $parent->id)
            ->where('normal_account', 'sub')
            ->lockForUpdate()
            ->max('account_code');

        // أول فرع فرعي
        if (! $lastCode) {
            $parentCode = (int) $parent->account_code;

            return match ($parent->account_type) {
                'asset' => (string) (($parentCode * 1000) + 1),
                'liability' => (string) (($parentCode * 10000) + 1),
                default => throw new \Exception(
                    'لا توجد قاعدة ترقيم للحساب الفرعي لهذا النوع من الحسابات.'
                ),
            };
        }

        // الفرع التالي
        return (string) ((int) $lastCode + 1);
    }

    /**
     * توليد كود لحساب جذري جديد (بدون أب).
     * النمط: آخر كود جذري + 1 (مثال: "5" → "6")
     */
    private function generateRootAccountCode(): string
    {
        $lastCode = Account::whereNull('parent_id')
            ->lockForUpdate()
            ->max('account_code');

        if (! $lastCode) {
            return '1';
        }

        return (string) ((int) $lastCode + 1);
    }
}
