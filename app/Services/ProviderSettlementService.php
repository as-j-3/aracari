<?php

namespace App\Services;

use App\Constants\AccountCodes;
use App\Constants\TransactionReferenceStatus;
use App\Constants\UserTypes;
use App\Models\Account;
use App\Models\Currency;
use App\Models\Transaction;
use App\Models\User;
use App\Repositories\Contracts\TransactionRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProviderSettlementService
{
    public function __construct(
        private AuditService $auditService,
        private TransactionRepositoryInterface $transactionRepository
    ) {}

    /**
     * تحويل account_code إلى account_id
     */
    private function getAccountIdByCode(string $accountCode): ?int
    {
        return Account::where('account_code', $accountCode)->value('id');
    }

    /**
     * جلب أرصدة مجموعة من account_codes دفعة واحدة
     * @return \Illuminate\Support\Collection
     */
    private function getBalancesByAccountCodes(array $accountCodes): \Illuminate\Support\Collection
    {
        return Transaction::selectRaw('accounts.account_code, transactions.currency_id, 
                    SUM(CASE WHEN transactions.transaction_type = "credit" THEN transactions.amount ELSE -transactions.amount END) as balance')
            ->join('accounts', 'transactions.account_id', '=', 'accounts.id')
            ->whereIn('accounts.account_code', $accountCodes)
            ->groupBy('accounts.account_code', 'transactions.currency_id')
            ->get()
            ->keyBy(fn ($item) => $item->account_code . '_' . $item->currency_id);
    }

    /**
     * جلب رصيد المزود المستحق
     */
    public function getProviderBalance(int $providerUserId, ?int $currencyId = null): float
    {
        $providerCode = AccountCodes::providerAccount($providerUserId);
        $accountId = $this->getAccountIdByCode($providerCode);

        if (!$accountId) {
            return 0.0;
        }

        $query = Transaction::where('account_id', $accountId);

        if ($currencyId) {
            $query->where('currency_id', $currencyId);
        }

        $credits = (clone $query)->where('transaction_type', 'credit')->sum('amount');
        $debits = (clone $query)->where('transaction_type', 'debit')->sum('amount');

        return round($credits - $debits, 2);
    }

    /**
     * جلب جميع المزودين مع أرصدتهم
     */
    public function getProvidersWithBalances(): array
    {
        $baseCurrencyId = \App\Models\Setting::where('key', 'base_currency_id')->value('value') ?? 1;

        // 1. جلب كل المزودين مرة واحدة
        $providers = User::where('type_user_code', UserTypes::PROVIDER)
            ->with('providerService')
            ->get();

        // 2. جلب كل أرصدة المزودين مرة واحدة باستخدام استعلام مجمع مع Join
        $providerCodes = $providers->map(fn ($p) => AccountCodes::providerAccount($p->id))->toArray();
        
        $balances = $this->getBalancesByAccountCodes($providerCodes);

        $currencies = Currency::orderBy('name')->get();
        $exchangeRateService = app(ExchangeRateService::class);

        $result = [];
        foreach ($providers as $provider) {
            $providerBalances = [];
            $totalBase = 0;
            $providerCode = AccountCodes::providerAccount($provider->id);

            foreach ($currencies as $currency) {
                $key = $providerCode . '_' . $currency->id;
                $balance = $balances[$key]->balance ?? 0;

                if ($balance > 0) {
                    try {
                        $rate = $exchangeRateService->getRate($currency->id, $baseCurrencyId);
                    } catch (\Throwable $e) {
                        $rate = 1; // حماية: إذا لم يُوجد سعر صرف
                    }
                    
                    $providerBalances[] = [
                        'currency_id' => $currency->id,
                        'currency_code' => $currency->code,
                        'amount' => $balance,
                        'rate' => $rate,
                        'base_amount' => round($balance / $rate, 2),
                    ];
                    $totalBase += round($balance / $rate, 2);
                }
            }

            if ($totalBase > 0) {
                $result[] = [
                    'id' => $provider->id,
                    'name' => $provider->name,
                    'email' => $provider->email,
                    'provider_service' => $provider->providerService?->name,
                    'balances' => $providerBalances,
                    'total_base' => $totalBase,
                    'revenue_methods' => $provider->providerService?->receiveRevenueMethods()
                        ->select('id', 'account_name', 'account_number')
                        ->get()
                        ->map(function ($method) {
                            return [
                                'id' => $method->id,
                                'account_name' => $method->account_name,
                                'account_number_last4' => $method->account_number_last4 ?? null,
                            ];
                        }),
                ];
            }
        }

        return $result;
    }

    /**
     * تنفيذ تسوية لمزود
     *
     * الأمان:
     * - فحص الرصيد داخل transaction مع lockForUpdate (منع double-settlement)
     * - التحقق من توازن القيد المحاسبي
     */
    public function settleProvider(
        int $providerUserId,
        float $amount,
        ?int $revenueMethodId = null,
        ?int $actorId = null,
        ?int $currencyId = null
    ): array {
        // التحقق الأولي من المبلغ
        if ($amount <= 0) {
            return ['success' => false, 'message' => 'يجب أن يكون مبلغ التسوية أكبر من صفر'];
        }

        $baseCurrencyId = (int) (\App\Models\Setting::where('key', 'base_currency_id')->value('value') ?? 1);
        $currencyId = $currencyId ?? $baseCurrencyId;

        try {
            $exchangeRate = app(ExchangeRateService::class)->getRate($currencyId, $baseCurrencyId);

            if (!$exchangeRate || $exchangeRate <= 0) {
                return ['success' => false, 'message' => 'سعر الصرف غير متاح للعملة المختارة'];
            }

            $provider = User::find($providerUserId);

            //  تأكد من وجود حسابات التسوية (CASH) قبل العملية
            $this->ensureSettlementAccountsExist($actorId ?? 1);

            //  كل العملية داخل transaction مع lockForUpdate على حساب المزود
            return DB::transaction(function () use ($providerUserId, $amount, $revenueMethodId, $actorId, $currencyId, $baseCurrencyId, $exchangeRate, $provider) {
                $providerCode = AccountCodes::providerAccount($providerUserId);
                $providerAccountId = Account::where('account_code', $providerCode)->value('id');

                if (!$providerAccountId) {
                    return [
                        'success' => false,
                        'message' => 'حساب المزود المحاسبي غير موجود',
                    ];
                }

                //  قفل صفوف المعاملات الخاصة بالمزود لمنع double-settlement
                $credits = Transaction::where('account_id', $providerAccountId)
                    ->where('currency_id', $currencyId)
                    ->where('transaction_type', 'credit')
                    ->lockForUpdate()
                    ->sum('amount');

                $debits = Transaction::where('account_id', $providerAccountId)
                    ->where('currency_id', $currencyId)
                    ->where('transaction_type', 'debit')
                    ->lockForUpdate()
                    ->sum('amount');

                $balance = round($credits - $debits, 2);

                //  فحص الرصيد داخل transaction (بعد القفل)
                if ($amount > $balance) {
                    return [
                        'success' => false,
                        'message' => 'مبلغ التسوية أكبر من الرصيد المستحق بهذه العملة',
                    ];
                }

                $description = 'تسوية مستحقات مزود - ' . ($provider->name ?? $providerUserId);
                if ($revenueMethodId) {
                    $revenueMethod = \App\Models\ReceiveRevenueMethod::find($revenueMethodId);
                    $description .= ' (طريقة الدفع: ' . ($revenueMethod->account_name ?? $revenueMethodId) . ')';
                }

                $reference = $this->transactionRepository->createReference([
                    'reference_number' => 'STL-' . strtoupper(Str::random(10)),
                    'reference_type' => 'provider_settlement',
                    'reference_id' => $providerUserId,
                    'created_by' => $actorId,
                    'status' => TransactionReferenceStatus::DRAFT,
                    'description' => $description,
                ]);

                // خصم من حساب المزود (مدين) — نستخدم account_id بدلاً من account_code
                $this->transactionRepository->createTransaction([
                    'transaction_reference_id' => $reference->id,
                    'account_id' => $providerAccountId,
                    'transaction_type' => 'debit',
                    'amount' => $amount,
                    'currency_id' => $currencyId,
                    'exchange_rate' => $exchangeRate,
                    'base_amount' => round($amount / $exchangeRate, 2),
                    'description' => $description,
                ]);

                // إخراج النقدية من الخزينة (دائن) — نستخدم account_id بدلاً من account_code
                $cashAccountId = Account::where('account_code', AccountCodes::CASH)->value('id');
                $this->transactionRepository->createTransaction([
                    'transaction_reference_id' => $reference->id,
                    'account_id' => $cashAccountId,
                    'transaction_type' => 'credit',
                    'amount' => $amount,
                    'currency_id' => $currencyId,
                    'exchange_rate' => $exchangeRate,
                    'base_amount' => round($amount / $exchangeRate, 2),
                    'description' => $description,
                ]);

                if (!$this->transactionRepository->validateEntryBalance($reference->id)) {
                    throw new \RuntimeException("خطأ محاسبي: قيد التسوية غير متوازن للمرجع {$reference->id}");
                }

                //  Audit في afterCommit
                DB::afterCommit(function () use ($actorId, $providerUserId, $amount, $currencyId, $revenueMethodId, $reference) {
                    $this->auditService->log($actorId, 'settlement', 'providers', $providerUserId, null, [
                        'amount' => $amount,
                        'currency_id' => $currencyId,
                        'revenue_method_id' => $revenueMethodId,
                        'reference' => $reference->reference_number,
                    ]);
                });

                return [
                    'success' => true,
                    'message' => 'تمت التسوية بنجاح',
                    'reference' => $reference->reference_number,
                    'amount' => $amount,
                    'new_balance' => round($balance - $amount, 2),
                ];
            });

        } catch (\Exception $e) {
            \Log::error('Settlement failed', [
                'provider_id' => $providerUserId,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'message' => 'فشلت عملية التسوية. يرجى المحاولة مرة أخرى.'];
        }
    }

    /**
     *  ضمان وجود حسابات التسوية الأساسية
     *
     * ينشئ الحسابات الفرعية اللازمة لعمليات التسوية إذا لم تكن موجودة:
     * - CASH (النقدية) تحت ASSETS
     *
     * @param int $actorId معرف المستخدم الذي ينفذ العملية
     * @return void
     * @throws \RuntimeException إذا فشل إنشاء الحسابات
     */
    private function ensureSettlementAccountsExist(int $actorId): void
    {
        $requiredAccounts = [
            [
                'code' => AccountCodes::CASH,
                'name' => 'النقدية',
                'type' => 'asset',
                'parent_code' => AccountCodes::ASSETS,
            ],
        ];

        foreach ($requiredAccounts as $accountConfig) {
            // تحقق من وجود الحساب
            $exists = Account::where('account_code', $accountConfig['code'])->exists();
            if ($exists) {
                continue;
            }

            // ابحث عن الـ parent
            $parentId = null;
            if ($accountConfig['parent_code']) {
                $parentId = Account::where('account_code', $accountConfig['parent_code'])->value('id');
                if (!$parentId) {
                    throw new \RuntimeException(
                        "الحساب الأصلي {$accountConfig['parent_code']} غير موجود. يرجى تشغيل الـ AccountingSeeder."
                    );
                }
            }

            Account::create([
                'uuid' => (string) Str::uuid(),
                'account_code' => $accountConfig['code'],
                'account_name' => $accountConfig['name'],
                'account_type' => $accountConfig['type'],
                'level' => 2,
                'parent_id' => $parentId,
                'created_by_user' => $actorId,
            ]);
        }
    }
}
