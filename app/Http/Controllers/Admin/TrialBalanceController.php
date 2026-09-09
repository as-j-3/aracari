<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Transaction;
use App\Services\CurrencyService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;

class TrialBalanceController extends Controller
{
    public function __construct(
        private CurrencyService $currencyService
    ) {
        $this->middleware('require.admin');
    }

    public function __invoke(Request $request)
    {
        try {
            $request->validate([
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
            ]);

            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            $accounts = Account::orderBy('account_code')->get();

            $balances = Transaction::join('accounts', 'accounts.id', '=', 'transactions.account_id')
                ->when($startDate, fn($q) => $q->whereDate('transactions.created_at', '>=', $startDate))
                ->when($endDate, fn($q) => $q->whereDate('transactions.created_at', '<=', $endDate))
                ->selectRaw('accounts.account_code as account_code,
                    SUM(CASE WHEN transaction_type = "debit" THEN base_amount ELSE 0 END) as total_debit,
                    SUM(CASE WHEN transaction_type = "credit" THEN base_amount ELSE 0 END) as total_credit')
                ->groupBy('accounts.account_code')
                ->get()
                ->keyBy('account_code');

            $accountBalances = $accounts->map(function (Account $account) use ($balances) {
                $txData = $balances->get($account->account_code);
                
                $debit = $txData?->total_debit ?? 0;
                $credit = $txData?->total_credit ?? 0;

                $netBalance = in_array($account->account_type, ['asset', 'expense'], true)
                    ? $debit - $credit
                    : $credit - $debit;

                return [
                    'account' => $account,
                    'debit' => $netBalance > 0 ? $netBalance : 0,
                    'credit' => $netBalance < 0 ? abs($netBalance) : 0,
                ];
            })->filter(fn (array $item) => $item['debit'] > 0 || $item['credit'] > 0)->values();

            $totalDebit = $accountBalances->sum('debit');
            $totalCredit = $accountBalances->sum('credit');

            $baseCurrencyCode = $this->currencyService->baseCurrency()->code ?? '-';

            return view('admin.reports.trial-balance', compact(
                'accountBalances', 'totalDebit', 'totalCredit', 'startDate', 'endDate', 'baseCurrencyCode'
            ));
        } catch (\Exception $e) {
            Log::error('Trial balance generation failed: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء توليد ميزان المراجعة. يرجى المحاولة لاحقاً.');
        }
    }
}