<?php

namespace App\Repositories\Contracts;

use App\Models\Transaction;
use App\Models\TransactionReference;

interface TransactionRepositoryInterface
{

    public function createReference(array $data): TransactionReference;


    public function createTransaction(array $data): Transaction;

    public function validateEntryBalance(int $referenceId): bool;
}