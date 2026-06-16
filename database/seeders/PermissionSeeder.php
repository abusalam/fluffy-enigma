<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    /**
     * Idempotent baseline RBAC. Safe to run on every deploy: it creates the
     * canonical permissions and starter roles, and prunes obsolete ones left
     * over from the removed scheme-monitoring module.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'dashboard.view',
            'shortlinks.view', 'shortlinks.manage', 'shortlinks.view_all',
            'users.view', 'users.manage',
            'roles.view', 'roles.manage',
            'permissions.view', 'permissions.manage',
            'settings.manage',
        ];

        foreach ($permissions as $name) {
            Permission::findOrCreate($name, 'web');
        }

        // super-admin: holds no explicit permissions; Gate::before grants all.
        Role::findOrCreate('super-admin', 'web');

        $administrator = Role::findOrCreate('administrator', 'web');
        $administrator->syncPermissions($permissions);

        $editor = Role::findOrCreate('editor', 'web');
        $editor->syncPermissions(['dashboard.view', 'shortlinks.view', 'shortlinks.manage']);

        $viewer = Role::findOrCreate('viewer', 'web');
        $viewer->syncPermissions(['dashboard.view', 'shortlinks.view']);

        // Prune artefacts of the removed scheme module.
        Permission::whereIn('name', ['schemes.view', 'schemes.manage'])->delete();
        Role::where('name', 'scheme-manager')->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
