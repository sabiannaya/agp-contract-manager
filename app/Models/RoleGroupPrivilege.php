<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoleGroupPrivilege extends Model
{
    use HasFactory;

    protected $table = 'role_group_privilege_mapping';

    protected $fillable = [
        'role_group_id',
        'page_id',
        '__create',
        '__read',
        '__update',
        '__delete',
    ];

    protected function casts(): array
    {
        return [
            '__create' => 'boolean',
            '__read' => 'boolean',
            '__update' => 'boolean',
            '__delete' => 'boolean',
        ];
    }

    public function roleGroup(): BelongsTo
    {
        return $this->belongsTo(RoleGroup::class, 'role_group_id');
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'page_id');
    }

    /**
     * Check if any privilege is granted.
     */
    public function hasAnyPrivilege(): bool
    {
        return $this->__create || $this->__read || $this->__update || $this->__delete;
    }

    /**
     * Check if all privileges are granted.
     */
    public function hasFullAccess(): bool
    {
        return $this->__create && $this->__read && $this->__update && $this->__delete;
    }
}
