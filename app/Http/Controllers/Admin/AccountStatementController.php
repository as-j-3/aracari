<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Transaction;
use App\Services\CurrencyService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;

class AccountStatementController extends Controller
{
    public function __construct(
        private CurrencyService $currencyService
    ) {
        $this->middleware('require.admin');
    }

    public function create(): View
    {
        $accounts = cache()->remember('accounts-list', 3600, fn() => Account::orderBy('account_code')->pluck('account_name', 'account_code'));
        return view('admin.reports.account-statement-form', compact('accounts'));
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'account_code' => 'required|exists:accounts,account_code',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
            ]);

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
                    'transaction' => $transaction,
                    'debit' => $transaction->transaction_type === 'debit' ? $transaction->base_amount : 0,
                    'credit' => $transaction->transaction_type === 'credit' ? $transaction->base_amount : 0,
                    'running_balance' => $runningBalance,
                ];
            });

            $closingBalance = $runningBalance;
            
            $baseCurrencyCode = $this->currencyService->baseCurrency()->code ?? '-';

            return view('admin.reports.account-statement', compact(
                'account', 'statementLines', 'openingBalance', 'closingBalance', 'startDate', 'endDate', 'baseCurrencyCode'
            ));
        } catch (\Exception $e) {
            Log::error('Account statement generation failed: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء توليد كشف الحساب. يرجى المحاولة لاحقاً.');
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