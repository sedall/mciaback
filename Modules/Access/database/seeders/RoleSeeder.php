<?php

namespace Modules\Access\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $guardName = 'web';

        $permissions = [
            'panel.admin.access',
            'panel.clinic.access',
            'panel.customer.access',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => $guardName,
            ]);
        }

        $roles = [
            'admin' => ['panel.admin.access'],
            'clinic' => ['panel.clinic.access'],
            'customer' => ['panel.customer.access'],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => $guardName,
            ]);

            $role->syncPermissions($rolePermissions);
        }
    }
}
