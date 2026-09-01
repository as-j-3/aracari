<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'module',
        'description',
    ];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permissions');
    }

    public function getModuleLabelAttribute(): string
    {
        return match ($this->module) {
            'accounting' => 'المحاسبة',
            'ads' => 'الإعلانات',
            'amenities' => 'المرافق',
            'bookings' => 'الحجوزات',
            'branches' => 'الفروع',
            'cities' => 'المدن',
            'commissions' => 'العمولات',
            'countries' => 'الدول',
            'Coupon Statuses' => 'حالات الكوبونات',
            'coupons' => 'الكوبونات',
            'files' => 'الملفات',
            'Login Activities' => 'سجلات الدخول',
            'passengers' => 'المسافرين',
            'Payment Methods' => 'وسائل الدفع',
            'provider_files' => 'ملفات المزودين',
            'providers' => 'المزودون',
            'provider' => 'لوحة المزود',
            'Reports' => 'التقارير',
            'roles' => 'الأدوار والصلاحيات',
            'settings' => 'الإعدادات',
            'taxes' => 'الضرائب',
            'Trip Levels' => 'مستويات الرحلات',
            'Trip Types' => 'أنواع الرحلات',
            'trips' => 'الرحلات',
            'users' => 'المستخدمون',
            'vehicle_types' => 'أنواع المركبات',
            'vehicles' => 'المركبات',
            default => $this->module ?: 'عام',
        };
    }

    public function getActionLabelAttribute(): string
    {
        $action = Str::after($this->name, ':');

        return match ($action) {
            'view' => 'عرض',
            'create' => 'إنشاء',
            'edit' => 'تعديل',
            'delete' => 'حذف',
            'toggle-status' => 'تفعيل أو تعطيل',
            'approve' => 'اعتماد',
            'assign' => 'تعيين',
            'assign-permissions' => 'تعيين صلاحيات',
            'trial-balance' => 'عرض ميزان المراجعة',
            'income-statement' => 'عرض قائمة الدخل',
            'balance-sheet' => 'عرض الميزانية العمومية',
            'account-statement' => 'عرض كشف الحساب',
            'audit' => 'عرض سجلات التدقيق',
            'financial' => 'عرض التقارير المالية',
            'export' => 'تصدير',
            'upload' => 'رفع',
            'cleanup' => 'تنظيف',
            'cancel' => 'إلغاء',
            'cancel-any' => 'إلغاء أي حجز',
            'view-any' => 'عرض الكل',
            'book-any' => 'الحجز للجميع',
            'manage' => 'إدارة',
            default => str_replace('-', ' ', $action),
        };
    }

    public function getDisplayDescriptionAttribute(): string
    {
        $description = trim((string) $this->description);

        if ($description !== '' && !str_contains($description, '?') && $description !== $this->name) {
            return $description;
        }

        return $this->buildFallbackDescription();
    }

    private function buildFallbackDescription(): string
    {
        $resource = Str::before($this->name, ':');
        $resourceLabel = match ($resource) {
            'accounts' => 'الحسابات',
            'journal' => 'القيود اليومية',
            'reports' => 'التقارير',
            'users' => 'المستخدمين',
            'roles' => 'الأدوار',
            'permissions' => 'الصلاحيات',
            'login-activities' => 'سجلات الدخول',
            'settings' => 'الإعدادات',
            'trips' => 'الرحلات',
            'vehicles' => 'المركبات',
            'vehicle_types' => 'أنواع المركبات',
            'countries' => 'الدول',
            'cities' => 'المدن',
            'amenities' => 'المرافق',
            'coupons' => 'الكوبونات',
            'coupon-statuses' => 'حالات الكوبونات',
            'taxes' => 'الضرائب',
            'payment-methods' => 'وسائل الدفع',
            'trip-levels' => 'مستويات الرحلات',
            'trip-types' => 'أنواع الرحلات',
            default => $resource,
        };

        return trim($this->action_label . ' ' . $resourceLabel);
    }
}
