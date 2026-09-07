<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CommissionPaymentMethod extends Model
{
    use HasFactory;

    protected $table = 'commission_payment_methods';

    protected $fillable = [
        'uuid',
        'payment_method_id',
        'com_type_rate_or_amount',
        'com_amount',
        'com_rate',
        'is_active',
    ];

    protected $casts = [
        'uuid' => 'string',
        'is_active' => 'boolean',
        'com_amount' => 'decimal:2',
        'com_rate' => 'decimal:2',
    ];

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

    // ==========================================
    // العلاقات (Relationships)
    // ==========================================

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    // ==========================================
    // Scopes
    // ==========================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
