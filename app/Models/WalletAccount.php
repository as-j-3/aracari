<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class WalletAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'currency_id',
        'account_id',
        'balance',
        'status',
    ];

    protected $casts = [
        'uuid' => 'string',
        'balance' => 'decimal:2',
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
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function transactions()
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
