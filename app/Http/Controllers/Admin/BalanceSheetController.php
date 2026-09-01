<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Constants\AccountCodes; 
use App\Models\Account;
use App\Models\Transaction;
use App\Services\CurrencyService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;

class BalanceSheetController extends Controller
{
    public function __construct(
        private CurrencyService $currencyService
    ) {
        $this->middleware('require.admin');
    }

    public function __invoke(Request $request)
    {
        try {
            $endDate = $request->input('end_date');

            $assetAccounts = Account::where('account_type', 'asset')->get();
            $liabilityAccounts = Account::where('account_type', 'liability')->get();
            $revenueAccounts = Account::where('account_code', AccountCodes::REVENUE)->get();
            $expenseAccounts = Account::where('account_code', AccountCodes::EXPENSES)->get();

            $assetDetails = $this->buildSection($assetAccounts, 'asset', $endDate);
            $liabilityDetails = $this->buildSection($liabilityAccounts, 'liability', $endDate);
            $revenueDetails = $this->buildSection($revenueAccounts, 'revenue', $endDate);
            $expenseDetails = $this->buildSection($expenseAccounts, 'expense', $endDate);

            $totalAssets = $assetDetails->sum('balance');
            $totalLiabilities = $liabilityDetails->sum('balance');
            $totalRevenue = $revenueDetails->sum('balance');
            $totalExpenses = $expenseDetails->sum('balance');
            $totalEquity = $totalRevenue - $totalExpenses;

            $baseCurrencyCode = $this->currencyService->baseCurrency()->code ?? '-';

            return view('admin.reports.balance-sheet', compact(
                'assetDetails', 'liabilityDetails', 'revenueDetails', 'expenseDetails',
                'totalAssets', 'totalLiabilities', 'totalRevenue', 'totalExpenses', 'totalEquity',
                'endDate', 'baseCurrencyCode'
            ));
        } catch (\Exception $e) {
            Log::error('Balance sheet generation failed: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء توليد الميزانية العمومية. يرجى المحاولة لاحقاً.');
        }
    }

    private function buildSection($accounts, string $type, ?string $endDate = null)
    {
        if ($accounts->isEmpty()) {
            return collect();
        }

        $accountCodes = $accounts->pluck('account_code');

        $balances = Transaction::whereIn('account_code', $accountCodes)
            ->when($endDate, fn($q) => $q->whereDate('created_at', '<=', $endDate))
            ->selectRaw('account_code, 
                SUM(CASE WHEN transaction_type = "debit" THEN base_amount ELSE 0 END) as total_debit,
                SUM(CASE WHEN transaction_type = "credit" THEN base_amount ELSE 0 END) as total_credit')
            ->groupBy('account_code')
            ->get()
            ->keyBy('account_code');

        return $accounts->map(function (Account $account) use ($type, $balances) {
            $txData = $balances->get($account->account_code);
            
            $totalDebit = $txData?->total_debit ?? 0;
            $totalCredit = $txData?->total_credit ?? 0;
            
            $balance = in_array($type, ['asset', 'expense'], true)
                ? $totalDebit - $totalCredit
                : $totalCredit - $totalDebit;

            return [
                'account' => $account,
                'balance' => $balance,
            ];
        })
        ->filter(fn (array $item) => $item['balance'] != 0)
        ->values();
    }
}