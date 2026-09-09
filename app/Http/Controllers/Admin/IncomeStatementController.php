<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Constants\AccountCodes;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IncomeStatementController extends Controller
{
    public function __construct()
    {
        $this->middleware('require.admin');
    }

    public function __invoke(Request $request): View
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $revenueDetails = $this->getTransactionsDetails(AccountCodes::REVENUE, 'credit', $startDate, $endDate);
        $expenseDetails = $this->getTransactionsDetails(AccountCodes::EXPENSES, 'debit', $startDate, $endDate);

        $totalRevenue = $revenueDetails->sum('base_amount');
        $totalExpenses = $expenseDetails->sum('base_amount');
        $netIncome = $totalRevenue - $totalExpenses;

        return view('admin.reports.income-statement', compact(
            'revenueDetails',
            'expenseDetails',
            'totalRevenue',
            'totalExpenses',
            'netIncome',
            'startDate',
            'endDate',
        ));
    }

    private function getTransactionsDetails(string $accountCode, string $type, ?string $startDate, ?string $endDate)
    {
        $query = Transaction::with('currency')
            ->join('accounts', 'accounts.id', '=', 'transactions.account_id')
            ->where('accounts.account_code', $accountCode)
            ->where('transaction_type', $type);

        if ($startDate) $query->whereDate('created_at', '>=', $startDate);
        if ($endDate) $query->whereDate('created_at', '<=', $endDate);

        return $query->get()->map(function ($tx) {
            return [
                'description' => $tx->description,
                'amount' => $tx->amount,
                'base_amount' => $tx->base_amount, 
                'currency' => $tx->currency->code ?? '-',
                'date' => $tx->created_at->format('Y-m-d'),
            ];
        });
    }
}