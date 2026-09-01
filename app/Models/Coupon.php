<?php

namespace App\Models;

use App\Traits\BelongsToProvider;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Constants\CouponStatus as CouponStatusConst;
use App\Models\CouponStatus as CouponStatusModel;
use Illuminate\Support\Str;

class Coupon extends Model
{
    use BelongsToProvider, HasFactory;

    protected $providerOwnershipColumn = 'user_id';
    protected $providerOwnershipScope = 'provider_user';

    protected $fillable = [
        'uuid',
        'code',
        'percentage',
        'user_id',
        'status_id',
        'start_date',
        'end_date',
        'usage_limit',
        'max_usage_per_user',
        'minimum_amount',
        'description',
    ];

    protected $casts = [
        'uuid' => 'string',
        'percentage' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'minimum_amount' => 'decimal:2',
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

    
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function status()
    {
        return $this->belongsTo(CouponStatusModel::class, 'status_id');
    }

    public function usages()
    {
        return $this->hasMany(CouponUsage::class);
    }

    public function couponUsages()
    {
        return $this->usages();
    }

    public function trips()
    {
        return $this->belongsToMany(Trip::class, 'coupon_trips');
    }


    public function getUsedCountAttribute(): int
    {
        return $this->usages()->count();
    }


    public function getIsExpiredAttribute(): bool
    {
        if ($this->status_id === CouponStatusConst::EXPIRED) {
            return true;
        }
        
        if ($this->end_date && now()->gt($this->end_date)) {
            return true;
        }
        
        return false;
    }

 
    public function getIsValidAttribute(): bool
    {
        return $this->isValid();
    }


    public function getDiscountTypeAttribute(): string
    {
        return 'percentage';
    }

  
    public function getDiscountValueAttribute()
    {
        return $this->percentage;
    }


    public function getUsagePercentageAttribute(): float
    {
        if (!$this->usage_limit || $this->usage_limit == 0) {
            return 0;
        }
        
        return round(($this->used_count / $this->usage_limit) * 100);
    }

    public function isValid(): bool
    {
        if ($this->status_id !== CouponStatusConst::ACTIVE) {
            return false;
        }

        $now = now();
        if ($this->start_date && $now->lt($this->start_date)) {
            return false;
        }
        if ($this->end_date && $now->gt($this->end_date)) {
            return false;
        }

        if ($this->usage_limit && $this->usages()->count() >= $this->usage_limit) {
            return false;
        }

        return true;
    }


    public function isExpired(): bool
    {
        return $this->is_expired;
    }

  
    public function isActive(): bool
    {
        return $this->status_id === CouponStatusConst::ACTIVE;
    }


    public function isFullyUsed(): bool
    {
        return $this->usage_limit && $this->usages()->count() >= $this->usage_limit;
    }


    public function incrementUsage(): bool
    {
        if (!$this->isValid()) {
            return false;
        }
        
        if ($this->usage_limit && $this->usages()->count() >= $this->usage_limit) {
            $this->update(['status_id' => CouponStatusConst::USED]);
        }
        return true;
    }

    // ===== Scopes =====

  
    public function scopeActive($query)
    {
        return $query->where('status_id', CouponStatusConst::ACTIVE);
    }

  
    public function scopeNotExpired($query)
    {
        return $query->where('status_id', CouponStatusConst::ACTIVE)
                     ->where(function ($q) {
                         $q->whereNull('end_date')
                           ->orWhere('end_date', '>=', now());
                     });
    }

  
    public function scopeExpired($query)
    {
        return $query->where(function ($q) {
            $q->where('status_id', CouponStatusConst::EXPIRED)
              ->orWhere('end_date', '<', now());
        });
    }
}