<?php

namespace Modules\Access\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $guardName = 'sanctum';

        $permissions = [
            'panel.admin.access',
            'panel.clinic.access',
            'panel.customer.access',
            'panel.customer.tickets.create',
            'panel.customer.tickets.view',
            'panel.customer.tickets.reply',
            'panel.clinic.tickets.create',
            'panel.clinic.tickets.view',
            'panel.clinic.tickets.reply',
            'panel.admin.tickets.view',
            'panel.admin.tickets.reply',
            'panel.admin.tickets.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate(
                 $permission,
                $guardName
           );
        }

        $roles = [
            'admin' => ['panel.admin.access'],
            'clinic' => ['panel.clinic.access'],
            'customer' => ['panel.customer.access'],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::findOrCreate(
                 $roleName,
                 $guardName
           );

            $role->syncPermissions($rolePermissions);
        }
    }
}
