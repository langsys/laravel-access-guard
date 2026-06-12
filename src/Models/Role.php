<?php

namespace Langsys\AccessGuard\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Langsys\AccessGuard\Concerns\HasUuid;

class Role extends Model
{
    use HasUuid;

    protected $table = 'roles';

    protected $fillable = ['value', 'label', 'sort_order'];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            config('access-guard.models.permission', Permission::class),
            'role_has_permissions',
            'role_id',
            'permission_id',
        );
    }

    public function hasPermission(Permission|string $permission): bool
    {
        $value = $permission instanceof Permission ? $permission->value : $permission;

        return $this->permissions()->where('value', $value)->exists();
    }

    /**
     * Grant permissions by their string value, creating any that don't exist yet.
     * Convenience for seeding.
     *
     * @param string|array<int, string> $permissions
     */
    public function grantPermissions(string|array $permissions): static
    {
        $permissionModel = config('access-guard.models.permission', Permission::class);

        $ids = collect((array) $permissions)->filter()->map(
            fn (string $value) => $permissionModel::firstOrCreate(['value' => $value])->getKey()
        );

        $this->permissions()->syncWithoutDetaching($ids->all());

        return $this;
    }
}
