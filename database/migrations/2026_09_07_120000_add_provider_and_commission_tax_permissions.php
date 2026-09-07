<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Permission;
use App\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            // provider-tax permissions
            [
                'name' => 'provider-tax:view',
                'module' => 'provider-tax',
                'description' => 'عرض ضرائب المزودين',
            ],
            [
                'name' => 'provider-tax:create',
                'module' => 'provider-tax',
                'description' => 'إضافة ضرائب المزودين',
            ],
            [
                'name' => 'provider-tax:edit',
                'module' => 'provider-tax',
                'description' => 'تعديل ضرائب المزودين',
            ],
            [
                'name' => 'provider-tax:delete',
                'module' => 'provider-tax',
                'description' => 'حذف ضرائب المزودين',
            ],

            // commission-tax permissions
            [
                'name' => 'commission-tax:view',
                'module' => 'commission-tax',
                'description' => 'عرض ضرائب العمولات',
            ],
            [
                'name' => 'commission-tax:create',
                'module' => 'commission-tax',
                'description' => 'إضافة ضرائب العمولات',
            ],
            [
                'name' => 'commission-tax:edit',
                'module' => 'commission-tax',
                'description' => 'تعديل ضرائب العمولات',
            ],
            [
                'name' => 'commission-tax:delete',
                'module' => 'commission-tax',
                'description' => 'حذف ضرائب العمولات',
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

        // إسناد الصلاحيات للأدوار
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            foreach ($createdPermissions as $perm) {
                if (!$adminRole->permissions()->where('permission_id', $perm->id)->exists()) {
                    $adminRole->permissions()->attach($perm->id);
                }
            }
        }

        $providerRole = Role::where('name', 'provider')->first();
        if ($providerRole) {
            $providerPermNames = ['provider-tax:view', 'provider-tax:create'];
            $providerPerms = Permission::whereIn('name', $providerPermNames)->get();
            foreach ($providerPerms as $perm) {
                if (!$providerRole->permissions()->where('permission_id', $perm->id)->exists()) {
                    $providerRole->permissions()->attach($perm->id);
                }
            }
        }
    }

    public function down(): void
    {
        $permNames = [
            'provider-tax:view',
            'provider-tax:create',
            'provider-tax:edit',
            'provider-tax:delete',
            'commission-tax:view',
            'commission-tax:create',
            'commission-tax:edit',
            'commission-tax:delete',
        ];

        Permission::whereIn('name', $permNames)->delete();
    }
};
