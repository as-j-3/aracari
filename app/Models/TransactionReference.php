<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Constants\TransactionReferenceStatus;
class TransactionReference extends Model
{
    use HasFactory;


    protected $fillable = [
        'uuid',
        'reference_number',
        'reference_type',
        'reference_id',
        'created_by',
        'status',
        'confirmed_by',
        'confirmed_at',
        'reversed_by',
        'reversed_at',
        'reversal_reference_id',
    ];

    protected $casts = [
        'uuid' => 'string',
        'confirmed_at' => 'datetime',
        'reversed_at' => 'datetime',
    ];

    /**
     * Get the route key name for URL generation.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            if (empty($model->reference_number)) {
                $model->reference_number = 'AUTO-' . strtoupper(Str::random(8));
            }
            if (empty($model->status)) {
                $model->status = TransactionReferenceStatus::DRAFT;
            }
        });
    }

    public function isDraft(): bool
    {
        return $this->status === TransactionReferenceStatus::DRAFT;
    }

    public function isConfirmed(): bool
    {
        return $this->status === TransactionReferenceStatus::CONFIRMED;
    }

    public function isReversed(): bool
    {
        return $this->status === TransactionReferenceStatus::REVERSED;
    }

    public function isCancelled(): bool
    {
        return $this->status === TransactionReferenceStatus::CANCELLED;
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function confirmer()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
    public function reverser()
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    public function reversalReference()
    {
        return $this->belongsTo(TransactionReference::class, 'reversal_reference_id');
    }
    
    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'transaction_reference_id');
    }

    public function isBalanced(): bool
    {
        $totalDebits = $this->transactions()->where('transaction_type', 'debit')->sum('amount');
        $totalCredits = $this->transactions()->where('transaction_type', 'credit')->sum('amount');

        return $totalDebits == $totalCredits;
    }

    public function getTotalAmount(): float
    {
        return $this->transactions()->where('transaction_type', 'debit')->sum('amount');
    }
}
