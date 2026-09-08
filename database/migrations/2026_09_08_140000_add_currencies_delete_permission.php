<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Permission;
use App\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        // إضافة صلاحية حذف العملات المفقودة
        $permission = Permission::firstOrCreate(
            ['name' => 'currencies:delete'],
            [
                'module' => 'currencies',
                'description' => 'حذف العملات',
            ]
        );

        // إسنادها لدور الأدمن
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole && !$adminRole->permissions()->where('permission_id', $permission->id)->exists()) {
            $adminRole->permissions()->attach($permission->id);
        }
    }

    public function down(): void
    {
        Permission::where('name', 'currencies:delete')->delete();
    }
};