<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\TransactionReference;
use App\Constants\TransactionReferenceStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Service for Journal Entry operations
 * 
 * Handles business logic for journal entry management
 * Follows SOLID principles with clean, maintainable code
 */
class JournalEntryService
{
        public function __construct(
        private AuditService $auditService
    ) {}
    /**
     * Create new journal entry with double-entry accounting validation
     * 
     * This method creates a new manual journal entry with:
     * - Balance validation (debit = credit)
     * - Account code validation
     * - Database transaction for data integrity
     * 
     * @param array $data Journal entry data
     * @param int $creatorId User ID of the creator
     * @return array Creation result with success status and message
     */
    public function createJournalEntry(array $data, int $creatorId): array
    {
        try {
            DB::beginTransaction();

            // Validate entry balance (debit = credit)
            $totalDebit = collect($data['lines'])->sum('debit');
            $totalCredit = collect($data['lines'])->sum('credit');

            if (abs($totalDebit - $totalCredit) > 0.01) {
                return [
                    'success' => false,
                    'message' => 'القيد غير متوازن. مجموع المدين يجب يساوي مجموع الدائن',
                ];
            }

            // Validate account codes
            foreach ($data['lines'] as $line) {
                if (!$this->validateAccountCode($line['account_code'])) {
                    return [
                        'success' => false,
                        'message' => "الحساب {$line['account_code']} غير موجود في دليل الحسابات",
                    ];
                }
            }

            // Create reference
            $reference = TransactionReference::create([
                'reference_number' => 'MANUAL-' . strtoupper(Str::random(8)),
                'reference_type' => 'manual_journal_entry',
                'reference_id' => null,
                'created_by' => $creatorId,
                'created_at' => $data['date'],
            ]);

            // Create transaction lines
            foreach ($data['lines'] as $line) {
                Transaction::create([
                    'transaction_reference_id' => $reference->id,
                    'account_code' => $line['account_code'],
                    'transaction_type' => $line['debit'] > 0 ? 'debit' : 'credit',
                    'amount' => $line['debit'] > 0 ? $line['debit'] : $line['credit'],
                    'description' => $line['description'] ?? $data['description'],
                    'created_at' => $data['date'],
                ]);
            }

            DB::commit();
            $this->auditService->log($creatorId, 'created', 'journal_entries', $reference->id, null, $reference->load('transactions')->toArray());
            return [
                'success' => true,
                'message' => 'تم إنشاء القيد المحاسبي بنجاح',
                'data' => $reference->load('transactions'),
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to create journal entry: ' . $e->getMessage(), [
                'journal_data' => $data,
                'creator_id' => $creatorId,
            ]);

            return [
                'success' => false,
                'message' => 'فشل إنشاء القيد المحاسبي. يرجى المحاولة مرة أخرى.',
            ];
        }
    }

    /**
     * Update journal entry with balance validation
     * 
     * @param TransactionReference $reference Reference to update
     * @param array $data Updated journal entry data
     * @return array Update result with success status and message
     */
    public function updateJournalEntry(TransactionReference $reference, array $data, int $creatorId): array
    {
        try {
            DB::beginTransaction();

            // Validate entry balance
            $totalDebit = collect($data['lines'])->sum('debit');
            $totalCredit = collect($data['lines'])->sum('credit');

            if (abs($totalDebit - $totalCredit) > 0.01) {
                return [
                    'success' => false,
                    'message' => 'القيد غير متوازن. مجموع المدين يجب يساوي مجموع الدائن',
                ];
            }

            // Validate account codes
            foreach ($data['lines'] as $line) {
                if (!$this->validateAccountCode($line['account_code'])) {
                    return [
                        'success' => false,
                        'message' => "الحساب {$line['account_code']} غير موجود في دليل الحسابات",
                    ];
                }
            }

            // Delete existing transactions
            $reference->transactions()->delete();

            // Create new transaction lines
            foreach ($data['lines'] as $line) {
                Transaction::create([
                    'transaction_reference_id' => $reference->id,
                    'account_code' => $line['account_code'],
                    'transaction_type' => $line['debit'] > 0 ? 'debit' : 'credit',
                    'amount' => $line['debit'] > 0 ? $line['debit'] : $line['credit'],
                    'description' => $line['description'] ?? $data['description'],
                    'created_at' => $data['date'],
                ]);
            }

            DB::commit();
            $this->auditService->log($creatorId, 'updated', 'journal_entries', $reference->id, null, $reference->load('transactions')->toArray());
            return [
                'success' => true,
                'message' => 'تم تحديث القيد المحاسبي بنجاح',
                'data' => $reference->fresh()->load('transactions'),
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update journal entry: ' . $e->getMessage(), [
                'reference_id' => $reference->id,
                'journal_data' => $data,
            ]);

            return [
                'success' => false,
                'message' => 'فشل تحديث القيد المحاسبي. يرجى المحاولة مرة أخرى.',
            ];
        }
    }

    /**
     * Delete journal entry
     * 
     * @param TransactionReference $reference Reference to delete
     * @return array Deletion result with success status and message
     */
    public function deleteJournalEntry(TransactionReference $reference, int $actorId): array
    {
        try {
            DB::beginTransaction();

            // Delete transactions first
            $reference->transactions()->delete();
            $reference->delete();

            DB::commit();
            $this->auditService->log($actorId, 'deleted', 'journal_entries', $reference->id, $reference->load('transactions')->toArray(), null);
            return [
                'success' => true,
                'message' => 'تم حذف القيد المحاسبي بنجاح',
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to delete journal entry: ' . $e->getMessage(), [
                'reference_id' => $reference->id,
            ]);

            return [
                'success' => false,
                'message' => 'فشل حذف القيد المحاسبي. يرجى المحاولة مرة أخرى.',
            ];
        }
    }

    /**
     * Validate account code exists
     * 
     * @param string $accountCode Account code to validate
     * @return bool True if account exists
     */
    private function validateAccountCode(string $accountCode): bool
    {
        //  تحقق من وجود الحساب في DB (وليس مجرد تطابق نمط regex)
        // هذا يمنع إدخال قيود لحسابات غير موجودة (والتي تكسر Double-Entry)
        return Account::where('account_code', $accountCode)->exists();
    }

    /**
     * Get journal entries with filtering
     * 
     * @param array $filters Filter criteria
     * @param int $perPage Items per page
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getJournalEntries(array $filters = [], int $perPage = 20)
    {
        $query = TransactionReference::with('transactions')
            ->orderBy('created_at', 'desc');

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('reference_number', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('description', 'like', '%' . $filters['search'] . '%');
            });
        }

            if (!empty($filters['status'])) {
        $query->where('status', $filters['status']);
    }

        return $query->paginate($perPage);
    }

    /**
     * Get empty line template for journal entry form
     * 
     * @return array Empty line structure
     */
    public function emptyLine(): array
    {
        return [
            'account_code' => '',
            'debit' => null,
            'credit' => null,
            'description' => '',
        ];
    }

    /**
     * Format transactions as lines for editing
     * 
     * @param TransactionReference $reference Reference with transactions
     * @return array Formatted lines
     */
    public function formatTransactionsAsLines(TransactionReference $reference): array
    {
        return $reference->transactions->map(function (Transaction $transaction) {
            return [
                'account_code' => $transaction->account_code,
                'debit' => $transaction->transaction_type === 'debit' ? (float) $transaction->amount : null,
                'credit' => $transaction->transaction_type === 'credit' ? (float) $transaction->amount : null,
                'description' => $transaction->description,
            ];
        })->values()->all();
    }

    /**
     * Mark journal entry as confirmed
     * 
     * @param TransactionReference $reference Reference to confirm
     * @param int $userId User ID of the confirmer
     * @return array Confirmation result with success status and message
     */
    public function markAsConfirmed(TransactionReference $reference, int $userId): array
    {
        if ($reference->isConfirmed()) {
            return ['success' => false, 'message' => 'القيد معتمد بالفعل'];
        }

        if (!$reference->isDraft()) {
            return ['success' => false, 'message' => 'فقط القيود المعلقة يمكن اعتمادها'];
        }

        try {
            DB::beginTransaction();

            $reference->update([
                'status' => TransactionReferenceStatus::CONFIRMED,
                'confirmed_by' => $userId,
                'confirmed_at' => now(),
            ]);

            $this->auditService->log($userId, 'confirmed', 'journal_entries', $reference->id, null, ['status' => 'confirmed']);

            DB::commit();

            return ['success' => true, 'message' => 'تم اعتماد القيد بنجاح'];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to confirm entry: ' . $e->getMessage());
            return ['success' => false, 'message' => 'فشل اعتماد القيد. يرجى المحاولة مرة أخرى.'];
        }
    }

    /**
     * Create reversal entry for a confirmed journal entry
     * 
     * @param TransactionReference $reference Reference to reverse
     * @param int $userId User ID of the reverser
     * @return array Reversal result with success status and message
     */
    public function createReversal(TransactionReference $reference, int $userId): array
    {
        if (!$reference->isConfirmed()) {
            return ['success' => false, 'message' => 'فقط القيود المعتمدة يمكن عكسها'];
        }

        try {
            DB::beginTransaction();

            // إنشاء قيد عكسي جديد
            $reversal = TransactionReference::create([
                'reference_number' => 'REV-' . strtoupper(Str::random(8)),
                'reference_type' => 'reversal',
                'reference_id' => $reference->id,
                'status' => TransactionReferenceStatus::DRAFT,
                'created_by' => $userId,
            ]);

            // عكس كل سطر في القيد الأصلي
            foreach ($reference->transactions as $transaction) {
                Transaction::create([
                    'transaction_reference_id' => $reversal->id,
                    'account_code' => $transaction->account_code,
                    'transaction_type' => $transaction->transaction_type === 'debit' ? 'credit' : 'debit',
                    'amount' => $transaction->amount,
                    'currency_id' => $transaction->currency_id,        
                    'exchange_rate' => $transaction->exchange_rate,     
                    'base_amount' => $transaction->base_amount,         
                    'description' => 'عكس: ' . ($transaction->description ?? ''),
                ]);
            }

            // تحديث القيد الأصلي
            $reference->update([
                'status' => TransactionReferenceStatus::REVERSED,
                'reversed_by' => $userId,
                'reversed_at' => now(),
                'reversal_reference_id' => $reversal->id,
            ]);

            //  Audit في afterCommit
            DB::afterCommit(function () use ($userId, $reference, $reversal) {
                $this->auditService->log($userId, 'reversed', 'journal_entries', $reference->id, null, [
                    'reversal_reference_id' => $reversal->id,
                ]);
            });

            DB::commit();

            return [
                'success' => true,
                'message' => 'تم عكس القيد بنجاح',
                'data' => $reversal->load('transactions'),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to reverse entry: ' . $e->getMessage());
            return ['success' => false, 'message' => 'فشل عكس القيد. يرجى المحاولة مرة أخرى.'];
        }
    }


    /**
     * Mark journal entry as cancelled
     * 
     * @param TransactionReference $reference Reference to cancel
     * @param int $userId User ID of the canceller
     * @return array Cancellation result with success status and message
     */
    public function markAsCancelled(TransactionReference $reference, int $userId): array
    {
        if (!$reference->isDraft()) {
            return ['success' => false, 'message' => 'فقط القيود المعلقة يمكن إلغاؤها'];
        }

        try {
            DB::beginTransaction();

            $reference->update([
                'status' => TransactionReferenceStatus::CANCELLED,
                'reversed_by' => $userId,
                'reversed_at' => now(),
            ]);

            //  Audit في afterCommit
            DB::afterCommit(function () use ($userId, $reference) {
                $this->auditService->log($userId, 'cancelled', 'journal_entries', $reference->id);
            });

            DB::commit();

            return ['success' => true, 'message' => 'تم إلغاء القيد بنجاح'];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to cancel entry: ' . $e->getMessage());
            return ['success' => false, 'message' => 'فشل إلغاء القيد. يرجى المحاولة مرة أخرى.'];
        }
    }
}
