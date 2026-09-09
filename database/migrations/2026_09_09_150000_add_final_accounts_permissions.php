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
                'name' => 'final-accounts:view',
                'module' => 'final-accounts',
                'description' => 'عرض الحسابات الختامية',
            ],
            [
                'name' => 'final-accounts:create',
                'module' => 'final-accounts',
                'description' => 'إضافة حسابات ختامية',
            ],
            [
                'name' => 'final-accounts:edit',
                'module' => 'final-accounts',
                'description' => 'تعديل الحسابات الختامية',
            ],
            [
                'name' => 'final-accounts:delete',
                'module' => 'final-accounts',
                'description' => 'حذف الحسابات الختامية',
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
            'final-accounts:view',
            'final-accounts:create',
            'final-accounts:edit',
            'final-accounts:delete',
        ];

        Permission::whereIn('name', $permNames)->delete();
    }
};