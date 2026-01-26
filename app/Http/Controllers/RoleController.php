<?php

namespace App\Http\Controllers;

use App\Http\Requests\RoleGroupRequest;
use App\Models\Page;
use App\Models\RoleGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RoleController extends Controller
{
    public function index(Request $request): Response
    {
        $query = RoleGroup::query()
            ->withCount('members');

        // Search functionality
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Sorting
        $sortField = $request->get('sort', 'name');
        $sortDirection = $request->get('direction', 'asc');
        $query->orderBy($sortField, $sortDirection);

        $roleGroups = $query->paginate(15)->withQueryString();

        // Transform for frontend compatibility
        $roleGroups->getCollection()->transform(function ($group) {
            return [
                'id' => $group->id,
                'name' => $group->name,
                'description' => $group->description,
                'is_active' => $group->is_active,
                'is_system' => $group->is_system,
                'users_count' => $group->members_count,
                'permissions_count' => $group->privileges()->count(),
                'created_at' => $group->created_at,
                'updated_at' => $group->updated_at,
            ];
        });

        return Inertia::render('Roles/Index', [
            'roles' => $roleGroups,
            'filters' => $request->only(['search', 'is_active', 'sort', 'direction']),
        ]);
    }

    public function create(): Response
    {
        $pages = Page::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('Roles/Create', [
            'pages' => $pages,
        ]);
    }

    public function store(RoleGroupRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $privileges = $data['privileges'] ?? [];
        unset($data['privileges']);

        $roleGroup = RoleGroup::create($data);
        $roleGroup->syncPrivileges($privileges);

        return redirect()
            ->route('roles.index')
            ->with('success', 'Role group created successfully.');
    }

    public function show(RoleGroup $role): Response
    {
        $role->load(['privileges.page', 'members']);

        // Transform privileges to include renamed boolean fields
        $role->privileges->transform(function ($privilege) {
            return [
                'page_id' => $privilege->page_id,
                'page' => $privilege->page,
                'create' => $privilege->__create,
                'read' => $privilege->__read,
                'update' => $privilege->__update,
                'delete' => $privilege->__delete,
            ];
        });

        return Inertia::render('Roles/Show', [
            'role' => $role,
        ]);
    }

    public function edit(RoleGroup $role): Response
    {
        $role->load('privileges.page');

        $pages = Page::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        // Transform privileges for easier frontend handling
        $privilegesByPage = [];
        foreach ($role->privileges as $privilege) {
            $privilegesByPage[$privilege->page_id] = [
                'create' => $privilege->__create,
                'read' => $privilege->__read,
                'update' => $privilege->__update,
                'delete' => $privilege->__delete,
            ];
        }

        return Inertia::render('Roles/Edit', [
            'role' => $role,
            'pages' => $pages,
            'privilegesByPage' => $privilegesByPage,
        ]);
    }

    public function update(RoleGroupRequest $request, RoleGroup $role): RedirectResponse
    {
        $data = $request->validated();
        $privileges = $data['privileges'] ?? [];
        unset($data['privileges']);

        $role->update($data);
        $role->syncPrivileges($privileges);

        return redirect()
            ->route('roles.index')
            ->with('success', 'Role group updated successfully.');
    }

    public function destroy(RoleGroup $role): RedirectResponse
    {
        // Prevent deletion of system role groups
        if ($role->is_system) {
            return redirect()
                ->route('roles.index')
                ->with('error', 'Cannot delete system role groups.');
        }

        $role->delete();

        return redirect()
            ->route('roles.index')
            ->with('success', 'Role group deleted successfully.');
    }
}
