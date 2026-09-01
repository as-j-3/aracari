<?php

namespace App\Services\Wallet;

use App\Models\WalletAccount;
use App\Models\WalletTransaction;
use App\Models\TransactionReference;
use App\Models\Transaction;
use App\Models\Account;
use App\Models\Setting;
use App\Constants\TransactionReferenceStatus;
use App\Constants\AccountCodes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\WalletDisabledException;

class WalletService
{
    /**
     * التحقق مما إذا كانت المحفظة مفعلة (مع cache)
     */
    public function isWalletEnabled(): bool
    {
        return cache()->remember('setting:wallet_feature_enabled', 60, function () {
            return Setting::where('key', 'wallet_feature_enabled')->value('value') === 'true';
        });
    }

    /**
     * التحقق مما إذا كان الدفع عبر المحفظة مفعلاً (مع cache)
     */
    public function isWalletPaymentEnabled(): bool
    {
        return cache()->remember('setting:wallet_payment_enabled', 60, function () {
            return Setting::where('key', 'wallet_payment_enabled')->value('value') === 'true';
        });
    }

    /**
     * التحقق مما إذا كان شحن المحفظة مفعلاً (مع cache)
     */
    public function isWalletTopupEnabled(): bool
    {
        return cache()->remember('setting:wallet_topup_enabled', 60, function () {
            return Setting::where('key', 'wallet_topup_enabled')->value('value') === 'true';
        });
    }

    /**
     * الحصول على محفظة المستخدم أو إنشائها إذا لم تكن موجودة
     * 
     * @param int $userId
     * @param int $currencyId
     * @return WalletAccount
     * @throws WalletDisabledException
     */
    public function getOrCreateAccount(int $userId, int $currencyId): WalletAccount
    {
        if (!$this->isWalletEnabled()) {
            throw new WalletDisabledException('نظام المحفظة غير مفعل حالياً.');
        }

        //  استخدم firstOrCreate مع lockForUpdate لمنع Race Condition
        return DB::transaction(function () use ($userId, $currencyId) {
            $walletAccount = WalletAccount::where('user_id', $userId)
                ->where('currency_id', $currencyId)
                ->lockForUpdate()
                ->first();

            if ($walletAccount) {
                return $walletAccount;
            }

            //  الحساب المحاسبي يجب أن يكون موجوداً، لا fallback لـ 1
            // ابحث عن حساب liability للمحفظة، أو fallback لأول حساب liability
            $accountId = Account::where('account_type', 'liability')
                ->where('code', 'LIKE', '2%')  // كل حسابات liability تبدأ بـ 2
                ->value('id');

            if (!$accountId) {
                throw new \RuntimeException('حساب المحفظة المحاسبي غير مهيأ. يرجى تشغيل الـ seeder.');
            }

            return WalletAccount::create([
                'uuid' => Str::uuid(),
                'user_id' => $userId,
                'currency_id' => $currencyId,
                'account_id' => $accountId,
                'balance' => 0,
                'status' => 'active',
            ]);
        });
    }

    /**
     * إضافة رصيد للمحفظة (Credit)
     * 
     * @param WalletAccount $account
     * @param float $amount
     * @param string $reason
     * @param string|null $referenceType
     * @param int|null $referenceId
     * @return WalletTransaction
     */
    public function credit(WalletAccount $account, float $amount, string $reason, ?string $referenceType = null, ?int $referenceId = null): WalletTransaction
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException("المبلغ يجب أن يكون أكبر من صفر.");
        }

        return DB::transaction(function () use ($account, $amount, $reason, $referenceType, $referenceId) {
            // 1. قفل الصف لمنع التلاعب المتزامن
            $account = WalletAccount::where('id', $account->id)->lockForUpdate()->first();

            if (!$account->isActive()) {
                throw new \Exception("المحفظة غير نشطة.");
            }

            $balanceBefore = (float) $account->balance;
            $balanceAfter = $balanceBefore + $amount;

            // 2. تحديث رصيد المحفظة
            $account->update(['balance' => $balanceAfter]);

            // 3. إنشاء القيد المحاسبي مع transactions فعلية
            $transactionRef = $this->createAccountingEntry(
                type: 'wallet_credit',
                walletAccount: $account,
                amount: $amount,
                description: $reason
            );

            // 4. تسجيل حركة المحفظة
            return WalletTransaction::create([
                'uuid' => Str::uuid(),
                'wallet_account_id' => $account->id,
                'transaction_reference_id' => $transactionRef->id,
                'type' => 'credit',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'description' => $reason,
            ]);
        });
    }

    /**
     * خصم رصيد من المحفظة (Debit)
     * 
     * @param WalletAccount $account
     * @param float $amount
     * @param string $reason
     * @param string|null $referenceType
     * @param int|null $referenceId
     * @return WalletTransaction
     * @throws InsufficientBalanceException
     */
    public function debit(WalletAccount $account, float $amount, string $reason, ?string $referenceType = null, ?int $referenceId = null): WalletTransaction
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException("المبلغ يجب أن يكون أكبر من صفر.");
        }

        return DB::transaction(function () use ($account, $amount, $reason, $referenceType, $referenceId) {
            // 1. قفل الصف
            $account = WalletAccount::where('id', $account->id)->lockForUpdate()->first();

            if (!$account->isActive()) {
                throw new \Exception("المحفظة غير نشطة.");
            }

            $balanceBefore = (float) $account->balance;

            if ($balanceBefore < $amount) {
                throw new InsufficientBalanceException("الرصيد غير كافٍ لإتمام هذه العملية.");
            }

            $balanceAfter = $balanceBefore - $amount;

            // 2. تحديث الرصيد
            $account->update(['balance' => $balanceAfter]);

            // 3. إنشاء القيد المحاسبي مع transactions فعلية
            $transactionRef = $this->createAccountingEntry(
                type: 'wallet_debit',
                walletAccount: $account,
                amount: $amount,
                description: $reason
            );

            // 4. تسجيل حركة المحفظة
            return WalletTransaction::create([
                'uuid' => Str::uuid(),
                'wallet_account_id' => $account->id,
                'transaction_reference_id' => $transactionRef->id,
                'type' => 'debit',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'description' => $reason,
            ]);
        });
    }

    /**
     * إنشاء قيد محاسبي مزدوج (Double-Entry Bookkeeping)
     * 
     * Credit: يزيد رصيد المستخدم (liability) + يقلل النقد (asset)
     * Debit: يقلل رصيد المستخدم (liability) + يزيد النقد (asset)
     */
    private function createAccountingEntry(
        string $type,
        WalletAccount $walletAccount,
        float $amount,
        string $description
    ): TransactionReference {
        // إنشاء القيد المرجعي
        $transactionRef = TransactionReference::create([
            'reference_number' => 'WAL-' . strtoupper(Str::random(10)),
            'reference_type' => $type,
            'reference_id' => $walletAccount->id,  //  wallet_account_id وليس user_id
            'created_by' => $walletAccount->user_id,
            'status' => TransactionReferenceStatus::CONFIRMED,
            'description' => $description,
        ]);

        // الحسابات المطلوبة
        $walletLiabilityAccount = $walletAccount->account_id;  // حساب liability المحفظة
        $cashAccount = Account::where('account_type', 'asset')
            ->where('code', 'LIKE', '1%')  // كل حسابات asset تبدأ بـ 1
            ->value('id');

        if (!$cashAccount) {
            throw new \RuntimeException('حساب النقد غير مهيأ. يرجى تشغيل الـ seeder.');
        }

        // Double-Entry: لكل قيد طرفين (debit + credit)
        if ($type === 'wallet_credit') {
            // شحن المحفظة: النقد يزيد (debit) + liability تزيد (credit)
            $this->createTransaction($transactionRef->id, $cashAccount, 'debit', $amount);
            $this->createTransaction($transactionRef->id, $walletLiabilityAccount, 'credit', $amount);
        } else {
            // سحب من المحفظة: liability تقل (debit) + النقد يقل (credit)
            $this->createTransaction($transactionRef->id, $walletLiabilityAccount, 'debit', $amount);
            $this->createTransaction($transactionRef->id, $cashAccount, 'credit', $amount);
        }

        return $transactionRef;
    }

    /**
     * إنشاء transaction line واحد
     */
    private function createTransaction(int $referenceId, int $accountId, string $type, float $amount): Transaction
    {
        return Transaction::create([
            'transaction_reference_id' => $referenceId,
            'account_id' => $accountId,
            'type' => $type,  // debit or credit
            'amount' => $amount,
        ]);
    }

    /**
     * مسح cache الإعدادات
     */
    public function clearSettingsCache(): void
    {
        cache()->forget('setting:wallet_feature_enabled');
        cache()->forget('setting:wallet_payment_enabled');
        cache()->forget('setting:wallet_topup_enabled');
    }
}
