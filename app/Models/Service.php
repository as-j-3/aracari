<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'branch_id',
        // name و description تم حذفهما - يمكن الحصول عليهما من branch->provider
    ];

    // Accessors للحصول على البيانات المحذوفة من provider مع الحماية من Null Pointer
    public function getNameAttribute(): ?string
    {
        if (!$this->branch || !$this->branch->provider) {
            return 'خدمة غير متاحة';
        }
        
        return $this->branch->provider->name ?? 'خدمة غير معروفة';
    }
    
    public function getDescriptionAttribute(): ?string
    {
        if (!$this->branch || !$this->branch->provider) {
            return null;
        }
        
        return $this->branch->provider->description ?? null;
    }
    
    public function getTypeOfServiceIdAttribute(): ?int
    {
        if (!$this->branch || !$this->branch->provider) {
            return null;
        }
        
        return $this->branch->provider->type_of_service_id ?? null;
    }
    
    public function getTypeOfServiceNameAttribute(): ?string
    {
        if (!$this->branch || !$this->branch->provider || !$this->branch->provider->typeOfService) {
            return null;
        }
        
        return $this->branch->provider->typeOfService->type_name ?? null;
    }
    
    public function getStatusAttribute(): string
    {
        // استخدام soft deletes لتحديد الحالة
        return $this->deleted_at ? 'inactive' : 'active';
    }
    
    public function isActive(): bool
    {
        return $this->deleted_at === null;
    }
    
    // Scope للخدمات النشطة
    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }
    
    public function scopeInactive($query)
    {
        return $query->whereNotNull('deleted_at');
    }

    public function branch()
    {
        return $this->belongsTo(ProviderBranch::class, 'branch_id');
    }

    // الحصول على بيانات المزود من خلال الفرع مع الحماية من Null Pointer
    public function provider()
    {
        return $this->branch?->provider();
    }

    // typeOfService يعمل من خلال provider مع الحماية من Null Pointer
    public function typeOfService()
    {
        if (!$this->branch || !$this->branch->provider) {
            return null;
        }
        
        return $this->branch->provider->typeOfService();
    }

    public function serviceCommissions()
    {
        return $this->hasMany(ServiceCommission::class);
    }
}
