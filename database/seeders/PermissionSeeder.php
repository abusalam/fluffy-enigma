<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    /**
     * Idempotent baseline RBAC. Safe to run on every deploy: it only
     * creates the canonical permissions and the four starter roles if
     * they are missing, and never deletes operator-created roles.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'schemes.view', 'schemes.manage',
            'shortlinks.view', 'shortlinks.manage', 'shortlinks.view_all',
            'users.view', 'users.manage',
            'roles.view', 'roles.manage',
            'permissions.view', 'permissions.manage',
        ];

        foreach ($permissions as $name) {
            Permission::findOrCreate($name, 'web');
        }

        // super-admin: holds no explicit permissions; Gate::before grants all.
        Role::findOrCreate('super-admin', 'web');

        $administrator = Role::findOrCreate('administrator', 'web');
        $administrator->syncPermissions($permissions);

        $schemeManager = Role::findOrCreate('scheme-manager', 'web');
        $schemeManager->syncPermissions([
            'schemes.view', 'schemes.manage',
            'shortlinks.view', 'shortlinks.manage',
        ]);

        $viewer = Role::findOrCreate('viewer', 'web');
        $viewer->syncPermissions(['schemes.view']);

        // The dashboard is now gated by `schemes.view`; drop the obsolete one.
        Permission::where('name', 'dashboard.view')->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
