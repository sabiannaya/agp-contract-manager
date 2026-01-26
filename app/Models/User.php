<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'preferred_lang',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * Get the role groups this user belongs to.
     */
    public function roleGroups(): BelongsToMany
    {
        return $this->belongsToMany(RoleGroup::class, 'group_members', 'user_id', 'role_group_id')
            ->withTimestamps();
    }

    /**
     * Check if user has a specific privilege on a page.
     */
    public function hasPrivilege(string $pageSlug, string $action): bool
    {
        $column = "__{$action}";

        return $this->roleGroups()
            ->where('is_active', true)
            ->whereHas('privileges', function ($query) use ($pageSlug, $column) {
                $query->whereHas('page', function ($pageQuery) use ($pageSlug) {
                    $pageQuery->where('slug', $pageSlug)->where('is_active', true);
                })->where($column, true);
            })
            ->exists();
    }

    /**
     * Check if user can perform action on a page (alias for hasPrivilege).
     */
    public function can($ability, $arguments = []): bool
    {
        // Handle permission checks in format "page.action"
        if (is_string($ability) && str_contains($ability, '.')) {
            [$pageSlug, $action] = explode('.', $ability, 2);
            return $this->hasPrivilege($pageSlug, $action);
        }

        return parent::can($ability, $arguments);
    }

    /**
     * Get all privileges for this user across all role groups.
     * Returns a map like: { "vendors.read": true, "vendors.create": true, ... }
     */
    public function getAllPrivileges(): array
    {
        $privileges = [];

        $roleGroups = $this->roleGroups()
            ->where('is_active', true)
            ->with(['privileges.page' => function ($query) {
                $query->where('is_active', true);
            }])
            ->get();

        foreach ($roleGroups as $group) {
            foreach ($group->privileges as $privilege) {
                if (!$privilege->page) {
                    continue;
                }
                
                $slug = $privilege->page->slug;
                
                if ($privilege->__read) {
                    $privileges["{$slug}.read"] = true;
                }
                if ($privilege->__create) {
                    $privileges["{$slug}.create"] = true;
                }
                if ($privilege->__update) {
                    $privileges["{$slug}.update"] = true;
                }
                if ($privilege->__delete) {
                    $privileges["{$slug}.delete"] = true;
                }
            }
        }

        return $privileges;
    }

    /**
     * Assign a role group to the user.
     */
    public function assignRoleGroup(RoleGroup $group): void
    {
        if (!$this->roleGroups()->where('role_group_id', $group->id)->exists()) {
            $this->roleGroups()->attach($group->id);
        }
    }

    /**
     * Remove a role group from the user.
     */
    public function removeRoleGroup(RoleGroup $group): void
    {
        $this->roleGroups()->detach($group->id);
    }

    /**
     * Sync user role groups.
     */
    public function syncRoleGroups(array $groupIds): void
    {
        $this->roleGroups()->sync($groupIds);
    }

    // ========================================
    // LEGACY: Keep old roles relationship for backward compatibility during migration
    // These can be removed after full migration to role groups
    // ========================================

    /**
     * @deprecated Use roleGroups() instead
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_role')
            ->withTimestamps();
    }
}
