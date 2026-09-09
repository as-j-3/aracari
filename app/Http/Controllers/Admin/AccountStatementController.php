<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Report\AccountStatementRequest;
use App\Http\Resources\Admin\AccountStatementResource;
use App\Models\Account;
use App\Models\Transaction;
use App\Services\CurrencyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class AccountStatementController extends Controller
{
    public function __construct(
        private CurrencyService $currencyService
    ) {
        $this->middleware('require.admin');
    }

    /**
     * عرض قائمة الحسابات المتاحة لتوليد كشف الحساب.
     */
    public function create(): JsonResponse
    {
        if (! auth()->user()->hasPermission('reports:account-statement')) {
            return $this->error('غير مصرح لك', null, 403);
        }

        $accounts = cache()->remember('accounts-list', 3600, fn() => Account::orderBy('account_code')->pluck('account_name', 'account_code'));

        /*
        // الكود السابق (Web View):
        return view('admin.reports.account-statement-form', compact('accounts'));
        */

        return $this->success([
            'accounts' => $accounts,
        ], 'تم جلب الحسابات بنجاح');
    }

    /**
     * توليد كشف الحساب (استجابة JSON API).
     */
    public function store(AccountStatementRequest $request): JsonResponse
    {
        if (! auth()->user()->hasPermission('reports:account-statement')) {
            return $this->error('غير مصرح لك', null, 403);
        }

        try {
            $validated = $request->validated();

            $account = Account::where('account_code', $validated['account_code'])->firstOrFail();
            $startDate = $validated['start_date'] ?? null;
            $endDate = $validated['end_date'] ?? null;

            $openingQuery = Transaction::where('account_id', $account->id)
                ->when($startDate, fn($q) => $q->whereDate('created_at', '<', $startDate))
                ->selectRaw("
                    SUM(CASE WHEN transaction_type = 'debit' THEN base_amount ELSE 0 END) as total_debit,
                    SUM(CASE WHEN transaction_type = 'credit' THEN base_amount ELSE 0 END) as total_credit
                ")->first();

            $debitSum = $openingQuery->total_debit ?? 0;
            $creditSum = $openingQuery->total_credit ?? 0;

            $openingBalance = in_array($account->account_type, ['asset', 'expense'], true)
                ? $debitSum - $creditSum
                : $creditSum - $debitSum;

            $transactions = Transaction::with(['reference', 'currency'])
                ->where('account_id', $account->id)
                ->when($startDate, fn ($query) => $query->whereDate('created_at', '>=', $startDate))
                ->when($endDate, fn ($query) => $query->whereDate('created_at', '<=', $endDate))
                ->orderBy('created_at')
                ->orderBy('id')
                ->limit(10000)
                ->get();

            $runningBalance = $openingBalance;
            $statementLines = $transactions->map(function (Transaction $transaction) use (&$runningBalance, $account) {
                $runningBalance += $this->signedAmount($transaction, $account);

                return [
                    'transaction' => [
                        'uuid' => $transaction->uuid,
                        'transaction_type' => $transaction->transaction_type,
                        'amount' => (float) $transaction->amount,
                        'base_amount' => (float) $transaction->base_amount,
                        'description' => $transaction->description,
                        'created_at' => $transaction->created_at->toDateTimeString(),
                        'reference' => $transaction->relationLoaded('reference') ? [
                            'uuid' => $transaction->reference->uuid,
                            'reference_number' => $transaction->reference->reference_number,
                            'created_by' => $transaction->reference->created_by,
                        ] : null,
                        'currency' => $transaction->relationLoaded('currency') ? [
                            'id' => $transaction->currency->id,
                            'code' => $transaction->currency->code,
                            'name' => $transaction->currency->name,
                        ] : null,
                    ],
                    'debit' => $transaction->transaction_type === 'debit' ? $transaction->base_amount : 0,
                    'credit' => $transaction->transaction_type === 'credit' ? $transaction->base_amount : 0,
                    'running_balance' => $runningBalance,
                ];
            });

            $closingBalance = $runningBalance;

            $baseCurrencyCode = $this->currencyService->baseCurrency()->code ?? '-';

            /*
            // الكود السابق (Web View):
            return view('admin.reports.account-statement', compact(
                'account', 'statementLines', 'openingBalance', 'closingBalance', 'startDate', 'endDate', 'baseCurrencyCode'
            ));
            */

            return $this->success(
                new AccountStatementResource($account, [
                    'opening_balance' => $openingBalance,
                    'statement_lines' => $statementLines,
                    'closing_balance' => $closingBalance,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'base_currency_code' => $baseCurrencyCode,
                ]),
                'تم توليد كشف الحساب بنجاح'
            );
        } catch (\Exception $e) {
            Log::error('Account statement generation failed: ' . $e->getMessage());
            return $this->error('حدث خطأ أثناء توليد كشف الحساب. يرجى المحاولة لاحقاً.');
        }
    }

    private function signedAmount(Transaction $transaction, Account $account): float
    {
        $amount = (float) $transaction->base_amount;

        if (in_array($account->account_type, ['asset', 'expense'], true)) {
            return $transaction->transaction_type === 'debit' ? $amount : -$amount;
        }

        return $transaction->transaction_type === 'credit' ? $amount : -$amount;
    }
}
