<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRoleRequest;
use App\Models\RoleGroup;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $query = User::query()
            ->with('roleGroups:id,name');

        // Search functionality
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by role group
        if ($roleGroupId = $request->get('role_id')) {
            $query->whereHas('roleGroups', function ($q) use ($roleGroupId) {
                $q->where('role_groups.id', $roleGroupId);
            });
        }

        // Sorting
        $sortField = $request->get('sort', 'name');
        $sortDirection = $request->get('direction', 'asc');
        $query->orderBy($sortField, $sortDirection);

        $users = $query->paginate(15)->withQueryString();

        // Transform users to include 'roles' property for frontend compatibility
        $users->getCollection()->transform(function ($user) {
            $userData = $user->toArray();
            $userData['roles'] = $user->roleGroups->map(fn($g) => ['id' => $g->id, 'name' => $g->name])->toArray();
            unset($userData['role_groups']);
            return $userData;
        });

        $roleGroups = RoleGroup::where('is_active', true)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return Inertia::render('Users/Index', [
            'users' => $users,
            'roles' => $roleGroups, // Keep as 'roles' for frontend compatibility
            'filters' => $request->only(['search', 'role_id', 'sort', 'direction']),
        ]);
    }

    public function edit(User $user): Response
    {
        $user->load('roleGroups');

        // Transform user to include 'roles' property for frontend compatibility
        $userData = $user->toArray();
        $userData['roles'] = $user->roleGroups->map(fn($g) => ['id' => $g->id, 'name' => $g->name, 'description' => $g->description])->toArray();
        unset($userData['role_groups']);

        $roleGroups = RoleGroup::where('is_active', true)
            ->select('id', 'name', 'description')
            ->orderBy('name')
            ->get();

        return Inertia::render('Users/Edit', [
            'user' => $userData,
            'roles' => $roleGroups, // Keep as 'roles' for frontend compatibility
        ]);
    }

    public function updateRoles(UserRoleRequest $request, User $user): RedirectResponse
    {
        $roleGroupIds = $request->input('roles', []);
        $user->syncRoleGroups($roleGroupIds);

        return redirect()
            ->route('users.index')
            ->with('success', 'User roles updated successfully.');
    }
}
