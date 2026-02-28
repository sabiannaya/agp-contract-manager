<?php

namespace Tests;

use App\Models\Page;
use App\Models\RoleGroup;
use App\Models\User;

trait CreatesUserWithPermissions
{
    /**
     * Create a user with full admin access to all pages.
     */
    protected function createAdminUser(): User
    {
        $user = User::factory()->create();
        $this->grantFullAccess($user);

        return $user;
    }

    /**
     * Create a user with specific page privileges.
     *
     * @param array<string, array<string>> $pagePrivileges e.g. ['contracts' => ['read', 'update'], 'tickets' => ['read']]
     */
    protected function createUserWithPrivileges(array $pagePrivileges): User
    {
        $user = User::factory()->create();
        $this->grantPrivileges($user, $pagePrivileges);

        return $user;
    }

    /**
     * Grant full CRUD access on all pages to a user.
     */
    protected function grantFullAccess(User $user): void
    {
        $group = RoleGroup::firstOrCreate(
            ['name' => 'Admin', 'is_system' => true],
            [
                'description' => 'Test admin role',
                'is_active' => true,
            ]
        );

        $this->ensurePagesExist();

        foreach (Page::all() as $page) {
            $group->privileges()->updateOrCreate(
                ['page_id' => $page->id],
                [
                    '__create' => true,
                    '__read' => true,
                    '__update' => true,
                    '__delete' => true,
                ]
            );
        }

        $user->syncRoleGroups([$group->id]);
    }

    /**
     * Grant specific privileges to a user.
     *
     * @param array<string, array<string>> $pagePrivileges
     */
    protected function grantPrivileges(User $user, array $pagePrivileges): void
    {
        $group = RoleGroup::create([
            'name' => 'TestRole_' . $user->id,
            'description' => 'Test role',
            'is_active' => true,
            'is_system' => false,
        ]);

        $this->ensurePagesExist();

        foreach ($pagePrivileges as $slug => $actions) {
            $page = Page::where('slug', $slug)->first();
            if (! $page) {
                continue;
            }

            $group->privileges()->create([
                'page_id' => $page->id,
                '__create' => in_array('create', $actions),
                '__read' => in_array('read', $actions),
                '__update' => in_array('update', $actions),
                '__delete' => in_array('delete', $actions),
            ]);
        }

        $user->syncRoleGroups([$group->id]);
    }

    /**
     * Ensure all pages from the constant exist in the database.
     */
    protected function ensurePagesExist(): void
    {
        foreach (Page::PAGES as $i => $slug) {
            Page::firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => ucfirst(str_replace('_', ' ', $slug)),
                    'description' => $slug . ' page',
                    'is_active' => true,
                    'sort_order' => $i + 1,
                ]
            );
        }
    }
}
