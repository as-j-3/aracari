<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'account_code',
        'account_name',
        'account_type',
        'level',
        'parent_id',
        'created_by_user',
    ];

    protected $casts = [
        'uuid' => 'string',
        'level' => 'integer',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function parent()
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Account::class, 'parent_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_user');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'account_id');
    }

    public function isAsset(): bool
    {
        return $this->account_type === 'asset';
    }

    public function isLiability(): bool
    {
        return $this->account_type === 'liability';
    }

    public function isRevenue(): bool
    {
        return $this->account_type === 'revenue';
    }

    public function isExpense(): bool
    {
        return $this->account_type === 'expense';
    }

    public function isEquity(): bool
    {
        return $this->account_type === 'equity';
    }

    public function getBalance(): float
    {
        $debits = $this->transactions()->where('transaction_type', 'debit')->sum('amount');
        $credits = $this->transactions()->where('transaction_type', 'credit')->sum('amount');

        return match($this->account_type) {
            'asset', 'expense' => $debits - $credits,
            'liability', 'revenue', 'equity' => $credits - $debits,
            default => 0
        };
    }

    public function getIndentedName(): string
    {
        return str_repeat('── ', $this->level - 1) . $this->account_name;
    }

    public function scopeMain($query)
    {
        return $query->where('level', 1);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('account_type', $type);
    }

    public function scopeChildrenOf($query, $parentId)
    {
        return $query->where('parent_id', $parentId);
    }
}
