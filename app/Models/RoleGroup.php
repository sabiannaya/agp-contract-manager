<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoleGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'is_active',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_system' => 'boolean',
        ];
    }

    /**
     * Get the privilege mappings for this role group.
     */
    public function privileges(): HasMany
    {
        return $this->hasMany(RoleGroupPrivilege::class, 'role_group_id');
    }

    /**
     * Get the users in this role group.
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'group_members', 'role_group_id', 'user_id')
            ->withTimestamps();
    }

    /**
     * Check if this role group has a specific privilege on a page.
     */
    public function hasPrivilege(string $pageSlug, string $action): bool
    {
        $column = "__{$action}";
        
        return $this->privileges()
            ->whereHas('page', function ($query) use ($pageSlug) {
                $query->where('slug', $pageSlug);
            })
            ->where($column, true)
            ->exists();
    }

    /**
     * Get privileges for a specific page.
     */
    public function getPagePrivileges(string $pageSlug): ?RoleGroupPrivilege
    {
        return $this->privileges()
            ->whereHas('page', function ($query) use ($pageSlug) {
                $query->where('slug', $pageSlug);
            })
            ->first();
    }

    /**
     * Set privileges for a page.
     */
    public function setPagePrivileges(int $pageId, array $privileges): void
    {
        $this->privileges()->updateOrCreate(
            ['page_id' => $pageId],
            [
                '__create' => $privileges['create'] ?? false,
                '__read' => $privileges['read'] ?? false,
                '__update' => $privileges['update'] ?? false,
                '__delete' => $privileges['delete'] ?? false,
            ]
        );
    }

    /**
     * Sync all privileges for this role group.
     */
    public function syncPrivileges(array $privilegesData): void
    {
        // Delete existing privileges
        $this->privileges()->delete();

        // Create new privileges
        foreach ($privilegesData as $data) {
            $this->privileges()->create([
                'page_id' => $data['page_id'],
                '__create' => $data['create'] ?? false,
                '__read' => $data['read'] ?? false,
                '__update' => $data['update'] ?? false,
                '__delete' => $data['delete'] ?? false,
            ]);
        }
    }
}
