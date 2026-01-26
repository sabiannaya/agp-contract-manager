<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [];

        foreach (Permission::RESOURCES as $resource) {
            foreach (Permission::ACTIONS as $action) {
                $permissions[] = [
                    'resource' => $resource,
                    'action' => $action,
                    'description' => "Can {$action} {$resource}",
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // Insert permissions (upsert to avoid duplicates)
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                [
                    'resource' => $permission['resource'],
                    'action' => $permission['action'],
                ],
                $permission
            );
        }
    }
}
