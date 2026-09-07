<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CommissionTax extends Model
{
    use HasFactory;

    protected $table = 'commission_taxes';

    protected $fillable = [
        'uuid',
        'country_tax_id',
        'service_id',
        'is_active',
    ];

    protected $casts = [
        'uuid' => 'string',
        'is_active' => 'boolean',
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

    /**
     * ضريبة الدولة المرتبطة (CountryTax)
     */
    public function countryTax(): BelongsTo
    {
        return $this->belongsTo(CountryTax::class, 'country_tax_id');
    }

    /**
     * الخدمة المرتبطة (Service) إن وجدت
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    // ==========================================
    // Scopes
    // ==========================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
