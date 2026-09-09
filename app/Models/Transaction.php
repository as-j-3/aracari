<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Transaction extends Model
{
    use HasFactory;

    /**
     * Attributes that are mass assignable
     */
    protected $fillable = [
        'uuid',
        'transaction_reference_id',
        'account_id',
        'transaction_type',
        'amount',
        'currency_id',
        'exchange_rate',
        'base_amount',
        'description',
    ];

    /**
     * Attribute casting for type safety
     */
    protected $casts = [
        'uuid' => 'string',
        'amount' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
        'base_amount' => 'decimal:2',
    ];

    /**
     * Get the route key name for URL generation.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Model boot method for automatic UUID generation
     * 
     * This method automatically generates a UUID for new transactions
     * if not provided during creation.
     */
    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Relationship: Transaction belongs to a transaction reference
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function reference()
    {
        return $this->belongsTo(TransactionReference::class, 'transaction_reference_id');
    }

    /**
     * Relationship: Transaction belongs to a currency
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }
    /**
     * Relationship: Transaction belongs to an account
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    /**
     * Check if transaction is a debit
     * 
     * Business logic:
     * - Debit transactions increase asset/expense accounts
     * - Debit transactions decrease liability/revenue accounts
     * 
     * @return bool True if transaction type is debit
     */
    public function isDebit(): bool
    {
        return $this->transaction_type === 'debit';
    }

    /**
     * Check if transaction is a credit
     * 
     * Business logic:
     * - Credit transactions decrease asset/expense accounts
     * - Credit transactions increase liability/revenue accounts
     * 
     * @return bool True if transaction type is credit
     */
    public function isCredit(): bool
    {
        return $this->transaction_type === 'credit';
    }

    /**
     * Scope: Filter for debit transactions
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query Query builder
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDebit($query)
    {
        return $query->where('transaction_type', 'debit');
    }

    /**
     * Scope: Filter for credit transactions
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query Query builder
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCredit($query)
    {
        return $query->where('transaction_type', 'credit');
    }

    /**
     * Scope: Filter transactions by account code
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query Query builder
     * @param string $accountCode Account code to filter by
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByAccount($query, string $accountCode)
    {
        return $query->where('account_code', $accountCode);
    }
    
}
