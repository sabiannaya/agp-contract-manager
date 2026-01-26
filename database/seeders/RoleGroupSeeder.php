<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\RoleGroup;
use App\Models\User;
use Illuminate\Database\Seeder;

class RoleGroupSeeder extends Seeder
{
    public function run(): void
    {
        // Create pages with is_active = true
        $pagesData = [
            ['slug' => 'dashboard', 'name' => 'Dashboard', 'description' => 'Main dashboard page', 'is_active' => true, 'sort_order' => 1],
            ['slug' => 'vendors', 'name' => 'Vendors', 'description' => 'Vendor management', 'is_active' => true, 'sort_order' => 2],
            ['slug' => 'contracts', 'name' => 'Contracts', 'description' => 'Contract management', 'is_active' => true, 'sort_order' => 3],
            ['slug' => 'tickets', 'name' => 'Tickets', 'description' => 'Ticket/work order management', 'is_active' => true, 'sort_order' => 4],
            ['slug' => 'role_groups', 'name' => 'Role Groups', 'description' => 'Role group management', 'is_active' => true, 'sort_order' => 5],
            ['slug' => 'users', 'name' => 'Users', 'description' => 'User management', 'is_active' => true, 'sort_order' => 6],
        ];

        $pages = [];
        foreach ($pagesData as $pageData) {
            $pages[] = Page::updateOrCreate(
                ['slug' => $pageData['slug']],
                $pageData
            );
        }

        // Create Admin role group with full access
        $adminGroup = RoleGroup::updateOrCreate(
            ['name' => 'Admin'],
            [
                'description' => 'Full system access',
                'is_active' => true,
                'is_system' => true,
            ]
        );

        // Grant all privileges to Admin
        foreach ($pages as $page) {
            $adminGroup->privileges()->updateOrCreate(
                ['page_id' => $page->id],
                [
                    '__create' => true,
                    '__read' => true,
                    '__update' => true,
                    '__delete' => true,
                ]
            );
        }

        // Create Viewer role group (read-only access)
        $viewerGroup = RoleGroup::updateOrCreate(
            ['name' => 'Viewer'],
            [
                'description' => 'Read-only access to all pages',
                'is_active' => true,
                'is_system' => false,
            ]
        );

        // Grant read privileges to Viewer
        foreach ($pages as $page) {
            $viewerGroup->privileges()->updateOrCreate(
                ['page_id' => $page->id],
                [
                    '__create' => false,
                    '__read' => true,
                    '__update' => false,
                    '__delete' => false,
                ]
            );
        }

        // Create Operator role group (CRUD on operational pages only)
        $operatorGroup = RoleGroup::updateOrCreate(
            ['name' => 'Operator'],
            [
                'description' => 'Manage vendors, contracts, and tickets',
                'is_active' => true,
                'is_system' => false,
            ]
        );

        // Grant privileges to Operator
        $operatorPages = ['dashboard', 'vendors', 'contracts', 'tickets'];
        foreach ($pages as $page) {
            if (in_array($page->slug, $operatorPages)) {
                $operatorGroup->privileges()->updateOrCreate(
                    ['page_id' => $page->id],
                    [
                        '__create' => $page->slug !== 'dashboard',
                        '__read' => true,
                        '__update' => $page->slug !== 'dashboard',
                        '__delete' => $page->slug !== 'dashboard',
                    ]
                );
            }
        }

        // Assign users to role groups
        $userRoleAssignments = [
            'admin@pln.co.id' => 'Admin',
            'supervisor@pln.co.id' => 'Operator',
            'staff@pln.co.id' => 'Operator',
            'manager@pln.co.id' => 'Viewer',
            'auditor@pln.co.id' => 'Viewer',
            'kontrak@pln.co.id' => 'Operator',
        ];

        foreach ($userRoleAssignments as $email => $groupName) {
            $user = User::where('email', $email)->first();
            $group = RoleGroup::where('name', $groupName)->first();
            
            if ($user && $group) {
                $user->syncRoleGroups([$group->id]);
            }
        }
    }
}
