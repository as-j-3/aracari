<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Permission;
use App\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            [
                'name' => 'direct-transfer-payment:view',
                'module' => 'direct-transfer-payment',
                'description' => 'عرض وسائل التحويل المباشر',
            ],
            [
                'name' => 'direct-transfer-payment:create',
                'module' => 'direct-transfer-payment',
                'description' => 'إضافة وسائل التحويل المباشر',
            ],
            [
                'name' => 'direct-transfer-payment:edit',
                'module' => 'direct-transfer-payment',
                'description' => 'تعديل وسائل التحويل المباشر',
            ],
            [
                'name' => 'direct-transfer-payment:delete',
                'module' => 'direct-transfer-payment',
                'description' => 'حذف وسائل التحويل المباشر',
            ],
        ];

        $createdPermissions = [];
        foreach ($permissions as $permData) {
            $createdPermissions[] = Permission::firstOrCreate(
                ['name' => $permData['name']],
                [
                    'module' => $permData['module'],
                    'description' => $permData['description'],
                ]
            );
        }

        // إسناد الصلاحيات لدور الأدمن
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            foreach ($createdPermissions as $perm) {
                if (!$adminRole->permissions()->where('permission_id', $perm->id)->exists()) {
                    $adminRole->permissions()->attach($perm->id);
                }
            }
        }
    }

    public function down(): void
    {
        $permNames = [
            'direct-transfer-payment:view',
            'direct-transfer-payment:create',
            'direct-transfer-payment:edit',
            'direct-transfer-payment:delete',
        ];

        Permission::whereIn('name', $permNames)->delete();
    }
};