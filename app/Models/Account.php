<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'account_code',
        'account_name',
        'account_type',
        'normal_account',
        'account_nature',
        'final_account_id',
        'parent_id',
        'created_by_user',
    ];

    protected $casts = [
        'uuid'            => 'string',
        'final_account_id' => 'integer',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    // ==================== العلاقات ====================

    /** الحساب الأب */
    public function parent()
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    /** الحسابات الفرعية المباشرة */
    public function children()
    {
        return $this->hasMany(Account::class, 'parent_id');
    }

    /** جميع الحسابات الفرعية بشكل تكراري */
    public function allChildren()
    {
        return $this->hasMany(Account::class, 'parent_id')->with('allChildren');
    }

    /** الحساب النهائي */
    public function finalAccount()
    {
        return $this->belongsTo(FinalAccount::class, 'final_account_id');
    }

    /** المستخدم المنشئ */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_user');
    }

    /** الحركات المالية */
    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'account_id');
    }

    // ==================== Scopes ====================

    /** الحسابات الرئيسية فقط */
    public function scopeMain($query)
    {
        return $query->where('normal_account', 'main');
    }

    /** الحسابات الفرعية فقط */
    public function scopeSub($query)
    {
        return $query->where('normal_account', 'sub');
    }

    /** تصفية حسب نوع الحساب */
    public function scopeByType($query, string $type)
    {
        return $query->where('account_type', $type);
    }

    /** أبناء حساب معين */
    public function scopeChildrenOf($query, $parentId)
    {
        return $query->where('parent_id', $parentId);
    }

    /** حسابات مدينة بطبيعتها */
    public function scopeDebit($query)
    {
        return $query->where('account_nature', 'debit');
    }

    /** حسابات دائنة بطبيعتها */
    public function scopeCredit($query)
    {
        return $query->where('account_nature', 'credit');
    }

    // ==================== Helpers ====================

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

    public function isMain(): bool
    {
        return $this->normal_account === 'main';
    }

    public function isSub(): bool
    {
        return $this->normal_account === 'sub';
    }

    /** حساب الرصيد مع مراعاة طبيعة الحساب */
    public function getBalance(): float
    {
        $debits  = $this->transactions()->where('transaction_type', 'debit')->sum('amount');
        $credits = $this->transactions()->where('transaction_type', 'credit')->sum('amount');

        return $this->account_nature === 'debit'
            ? $debits - $credits
            : $credits - $debits;
    }
}
