<?php

namespace App\Repositories;

use App\Constants\AccountCodes;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\TransactionReference;
use App\Repositories\Contracts\TransactionRepositoryInterface;
use Illuminate\Support\Facades\Log;

class TransactionRepository implements TransactionRepositoryInterface
{
    public function createReference(array $data): TransactionReference
    {
        return TransactionReference::create($data);
    }

    public function createTransaction(array $data): Transaction
    {
        if (isset($data['account_code']) && !isset($data['account_id'])) {
            $account = Account::where('account_code', $data['account_code'])->first();

            if (!$account) {
                $account = $this->ensureAccountExists($data['account_code']);
            }

            if ($account) {
                $data['account_id'] = $account->id;
            } else {
                Log::warning('Transaction created without account_id: account_code not resolved', [
                    'account_code' => $data['account_code'],
                ]);
            }

            unset($data['account_code']);
        }

        return Transaction::create($data);
    }


    private function ensureAccountExists(string $code): ?Account
    {
        $accountType = AccountCodes::getAccountType($code);

        if (!$accountType) {
            Log::warning('Cannot determine account type for code', ['code' => $code]);
            return null;
        }

        $parentId = null;
        $level   = 1;

        if (str_contains($code, '_')) {
            $parentCode = explode('_', $code, 2)[0];
            $parent = Account::where('account_code', $parentCode)->first();
            if ($parent) {
                $parentId = $parent->id;
                $level   = $parent->level + 1;
            }
        }

        return Account::create([
            'account_code'    => $code,
            'account_name'    => $code,
            'account_type'    => $accountType,
            'level'           => $level,
            'parent_id'       => $parentId,
            'created_by_user' => auth()->id() ?? 1,
        ]);
    }

    public function validateEntryBalance(int $referenceId): bool
    {
        $debits = Transaction::where('transaction_reference_id', $referenceId)
            ->where('transaction_type', 'debit')
            ->sum('base_amount');
            
        $credits = Transaction::where('transaction_reference_id', $referenceId)
            ->where('transaction_type', 'credit')
            ->sum('base_amount');

        $difference = abs($debits - $credits);

        if ($difference > 0.01) {
            Log::critical('UNBALANCED ACCOUNTING ENTRY DETECTED!', [
                'reference_id' => $referenceId,
                'debits' => $debits,
                'credits' => $credits,
                'difference' => $difference
            ]);
            return false;
        }

        return true;
    }
}
