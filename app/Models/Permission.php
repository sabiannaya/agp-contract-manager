<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    use HasFactory;

    public const ACTION_READ = 'read';
    public const ACTION_CREATE = 'create';
    public const ACTION_UPDATE = 'update';
    public const ACTION_DELETE = 'delete';

    public const ACTIONS = [
        self::ACTION_READ,
        self::ACTION_CREATE,
        self::ACTION_UPDATE,
        self::ACTION_DELETE,
    ];

    public const RESOURCES = [
        'vendors',
        'contracts',
        'tickets',
        'roles',
        'users',
    ];

    protected $fillable = [
        'resource',
        'action',
        'description',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permission')
            ->withTimestamps();
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->resource}.{$this->action}";
    }

    public static function findByResourceAction(string $resource, string $action): ?self
    {
        return static::where('resource', $resource)
            ->where('action', $action)
            ->first();
    }
}
