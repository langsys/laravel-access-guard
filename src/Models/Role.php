<?php

namespace Langsys\AccessGuard\Models;

use BackedEnum;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Model;
use Langsys\AccessGuard\Concerns\HasUuid;
use Langsys\AccessGuard\Concerns\RefreshesPermissionCache;
use Langsys\AccessGuard\Events\PermissionAssignedToRole;
use Langsys\AccessGuard\Events\PermissionRemovedFromRole;
use Langsys\AccessGuard\PermissionRegistrar;
use Langsys\AccessGuard\Support\Config;

class Role extends Model
{
    use HasUuid;
    use RefreshesPermissionCache;

    protected $fillable = ['value', 'label', 'sort_order'];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function getTable(): string
    {
        return Config::table('roles');
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            config('access-guard.models.permission', Permission::class),
            Config::table('role_has_permissions'),
            'role_id',
            'permission_id',
        );
    }

    public function hasPermission(Permission|string|BackedEnum $permission): bool
    {
        $value = $permission instanceof Permission ? $permission->value : Config::value($permission);

        return app(PermissionRegistrar::class)->roleHasPermission($this->getKey(), $value);
    }

    /**
     * Grant permissions by value, creating any that don't exist yet.
     *
     * @param string|BackedEnum|array<int, string|BackedEnum> $permissions
     */
    public function grantPermissions(string|BackedEnum|array $permissions): static
    {
        $permissionModel = config('access-guard.models.permission', Permission::class);

        $models = collect(Config::values($permissions))
            ->unique()
            ->map(fn (string $value) => $permissionModel::firstOrCreate(['value' => $value]));

        $this->permissions()->syncWithoutDetaching($models->pluck('id')->all());

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        if (config('access-guard.events_enabled', false)) {
            $models->each(fn ($permission) => event(new PermissionAssignedToRole($this, $permission)));
        }

        return $this;
    }

    /**
     * @param string|BackedEnum|array<int, string|BackedEnum> $permissions
     */
    public function revokePermissions(string|BackedEnum|array $permissions): static
    {
        $models = $this->permissions()->whereIn('value', Config::values($permissions))->get();

        $this->permissions()->detach($models->pluck('id')->all());

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        if (config('access-guard.events_enabled', false)) {
            $models->each(fn ($permission) => event(new PermissionRemovedFromRole($this, $permission)));
        }

        return $this;
    }
}
