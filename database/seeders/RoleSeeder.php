<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Admin role with all permissions
        $adminRole = Role::firstOrCreate(
            ['name' => 'Admin'],
            [
                'description' => 'Full access to all system features',
                'is_active' => true,
            ]
        );

        // Assign all permissions to Admin
        $allPermissionIds = Permission::pluck('id')->toArray();
        $adminRole->syncPermissions($allPermissionIds);

        // Create Editor role with create, read, update (no delete)
        $editorRole = Role::firstOrCreate(
            ['name' => 'Editor'],
            [
                'description' => 'Can view and modify data, but cannot delete',
                'is_active' => true,
            ]
        );

        $editorPermissionIds = Permission::whereIn('action', ['read', 'create', 'update'])
            ->whereIn('resource', ['vendors', 'contracts', 'tickets'])
            ->pluck('id')
            ->toArray();
        $editorRole->syncPermissions($editorPermissionIds);

        // Create Viewer role with read-only access
        $viewerRole = Role::firstOrCreate(
            ['name' => 'Viewer'],
            [
                'description' => 'Read-only access to view data',
                'is_active' => true,
            ]
        );

        $viewerPermissionIds = Permission::where('action', 'read')
            ->whereIn('resource', ['vendors', 'contracts', 'tickets'])
            ->pluck('id')
            ->toArray();
        $viewerRole->syncPermissions($viewerPermissionIds);
    }
}
