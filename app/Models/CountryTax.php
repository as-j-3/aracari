<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CountryTax extends Model
{
    use HasFactory;

    /**
     * اسم الجدول في قاعدة البيانات
     */
    protected $table = 'country_taxes';

    /**
     * الحقول المسموح بإدخالها وتعديلها
     */
    protected $fillable = [
        'uuid',
        'tax_id',
        'country_id',
        'type',
        'rate',
        'amount',
        'is_active',
    ];

    /**
     * تحويل أنواع البيانات أوتوماتيكياً
     */
    protected $casts = [
        'uuid' => 'string',
        'is_active' => 'boolean',
        'rate' => 'decimal:2',
        'amount' => 'decimal:2',
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
                $model->uuid = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    // ==========================================
    // العلاقات (Relationships)
    // ==========================================

    /**
     * جلب الضريبة الأساسية المرتبطة بهذا السجل
     * (ينتمي إلى Tax)
     */
    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class, 'tax_id');
    }

    /**
     * جلب الدولة المرتبطة بهذه الضريبة
     * (ينتمي إلى Country)
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    /**
     * جلب قائمة ضرائب المزودين المتربطة بهذه الضريبة
     * (يمتلك عدة ProviderTaxes)
     */
    public function providerTaxes(): HasMany
    {
        return $this->hasMany(ProviderTax::class, 'country_tax_id');
    }

    /**
     * جلب قائمة ضرائب العمولات المرتبطة بهذه الضريبة
     * (يمتلك عدة CommissionTaxes)
     */
    public function commissionTaxes(): HasMany
    {
        return $this->hasMany(CommissionTax::class, 'country_tax_id');
    }

    // ==========================================
    // نطاقات الاستعلام (Scopes)
    // ==========================================

    /**
     * Scope لتصفية السجلات النشطة فقط
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope لتصفية الضرائب بحسب الدولة
     */
    public function scopeForCountry($query, int $countryId)
    {
        return $query->where('country_id', $countryId);
    }
}