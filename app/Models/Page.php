<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Page extends Model
{
    use HasFactory;

    /**
     * The available pages/modules in the system.
     */
    public const PAGES = [
        'dashboard',
        'vendors',
        'contracts',
        'tickets',
        'role_groups',
        'users',
    ];

    protected $fillable = [
        'slug',
        'name',
        'description',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function privileges(): HasMany
    {
        return $this->hasMany(RoleGroupPrivilege::class, 'page_id');
    }

    /**
     * Get all active pages.
     */
    public static function getActivePages()
    {
        return static::where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Find page by slug.
     */
    public static function findBySlug(string $slug): ?self
    {
        return static::where('slug', $slug)->first();
    }
}
