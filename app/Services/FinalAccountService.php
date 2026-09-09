<?php

namespace App\Services;

use App\Models\FinalAccount;

class FinalAccountService
{
    public function getAll()
    {
        return FinalAccount::withCount('accounts')->orderBy('final_account_id')->get();
    }

    public function findById(int $id): ?FinalAccount
    {
        return FinalAccount::withCount('accounts')->find($id);
    }

    public function create(array $data): FinalAccount
    {
        return FinalAccount::create($data);
    }

    public function update(FinalAccount $finalAccount, array $data): FinalAccount
    {
        $finalAccount->update($data);
        return $finalAccount->fresh();
    }

    public function delete(FinalAccount $finalAccount): bool
    {
        return $finalAccount->delete();
    }
}
