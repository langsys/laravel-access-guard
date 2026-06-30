<?php

namespace Langsys\AccessGuard\Concerns;

use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Langsys\AccessGuard\Contracts\GuardableResource;
use Langsys\AccessGuard\Events\PermissionAssignedToModel;
use Langsys\AccessGuard\Events\PermissionRemovedFromModel;
use Langsys\AccessGuard\Events\RoleAssignedToModel;
use Langsys\AccessGuard\Events\RoleRemovedFromModel;
use Langsys\AccessGuard\Exceptions\RoleDoesNotExist;
use Langsys\AccessGuard\Models\ModelHasPermission;
use Langsys\AccessGuard\Models\ModelHasRole;
use Langsys\AccessGuard\Models\Permission;
use Langsys\AccessGuard\Models\Role;
use Langsys\AccessGuard\PermissionRegistrar;
use Langsys\AccessGuard\Support\Config;
use Langsys\AccessGuard\Support\Wildcard;

/**
 * Batteries-included entity-scoped access for a subject (usually the user model).
 * Provides role assignment (assign/sync/has), direct permission grants
 * (give/revoke), and the AuthorizableInEntity permission check — backed by the
 * model_has_roles and model_has_permissions pivots.
 *
 * @see \Langsys\AccessGuard\Contracts\AuthorizableInEntity
 */
trait HasRolesInEntities
{
    /** @var array<string, array<int, string>> */
    protected array $entityRoleIdCache = [];

    /** @var array<string, array<int, string>> */
    protected array $entityDirectPermissionCache = [];

    public function assignRole(Role|string|BackedEnum $role, GuardableResource $entity): static
    {
        $role = $this->resolveRoleModel($role);

        ModelHasRole::query()->firstOrCreate([
            'role_id' => $role->getKey(),
            'model_type' => $this->getMorphClass(),
            'model_id' => (string) $this->getKey(),
            'entity_type' => $entity->getMorphClass(),
            'entity_id' => (string) $entity->getKey(),
        ]);

        $this->entityRoleIdCache = [];
        $this->fireGuardEvent(new RoleAssignedToModel($this, $role, $entity));

        return $this;
    }

    public function removeRole(Role|string|BackedEnum $role, GuardableResource $entity): static
    {
        $role = $this->resolveRoleModel($role);

        $this->modelRoleQuery($entity)->where('role_id', $role->getKey())->delete();

        $this->entityRoleIdCache = [];
        $this->fireGuardEvent(new RoleRemovedFromModel($this, $role, $entity));

        return $this;
    }

    /**
     * @param array<int, Role|string|BackedEnum> $roles
     */
    public function syncRoles(array $roles, GuardableResource $entity): static
    {
        $this->modelRoleQuery($entity)->delete();
        $this->entityRoleIdCache = [];

        foreach ($roles as $role) {
            $this->assignRole($role, $entity);
        }

        return $this;
    }

    /**
     * Grant a permission directly to this subject within the entity, outside any
     * role. Creates the permission if it doesn't exist yet.
     */
    public function givePermission(Permission|string|BackedEnum $permission, GuardableResource $entity): static
    {
        $permission = $this->resolvePermissionModel($permission);

        ModelHasPermission::query()->firstOrCreate([
            'permission_id' => $permission->getKey(),
            'model_type' => $this->getMorphClass(),
            'model_id' => (string) $this->getKey(),
            'entity_type' => $entity->getMorphClass(),
            'entity_id' => (string) $entity->getKey(),
        ]);

        $this->entityDirectPermissionCache = [];
        $this->fireGuardEvent(new PermissionAssignedToModel($this, $permission, $entity));

        return $this;
    }

    public function revokePermission(Permission|string|BackedEnum $permission, GuardableResource $entity): static
    {
        $permission = $this->resolvePermissionModel($permission);

        $this->modelPermissionQuery($entity)->where('permission_id', $permission->getKey())->delete();

        $this->entityDirectPermissionCache = [];
        $this->fireGuardEvent(new PermissionRemovedFromModel($this, $permission, $entity));

        return $this;
    }

    public function rolesInEntity(GuardableResource $entity): Collection
    {
        $ids = $this->roleIdsInEntity($entity);
        $model = config('access-guard.models.role', Role::class);

        return $ids === []
            ? $model::query()->whereRaw('1 = 0')->get()
            : $model::query()->whereKey($ids)->get();
    }

    public function roleInEntity(GuardableResource $entity): ?Role
    {
        return $this->rolesInEntity($entity)->sortByDesc('sort_order')->first();
    }

    public function hasRole(Role|string|BackedEnum $role, GuardableResource $entity): bool
    {
        $role = $this->resolveRoleModel($role, fail: false);

        return $role !== null && in_array($role->getKey(), $this->roleIdsInEntity($entity), true);
    }

    public function hasPermissionInEntity(string|BackedEnum $permission, mixed $entity): bool
    {
        if (! $entity instanceof GuardableResource || $this->entityIsDisabled($entity)) {
            return false;
        }

        $value = Config::value($permission);
        $held = $this->permissionsInEntity($entity);

        if (config('access-guard.wildcard.enabled', false)) {
            return Wildcard::matches($held, $value, config('access-guard.wildcard.separator', '.'));
        }

        return in_array($value, $held, true);
    }

    /**
     * Every permission value the subject holds in the entity — the union of its
     * roles' permissions and any directly-granted permissions.
     *
     * @return array<int, string>
     */
    public function permissionsInEntity(GuardableResource $entity): array
    {
        if ($this->entityIsDisabled($entity)) {
            return [];
        }

        $map = app(PermissionRegistrar::class)->rolePermissionMap();
        $values = [];

        foreach ($this->roleIdsInEntity($entity) as $roleId) {
            foreach ($map[$roleId] ?? [] as $value) {
                $values[$value] = true;
            }
        }

        foreach ($this->directPermissionsInEntity($entity) as $value) {
            $values[$value] = true;
        }

        return array_keys($values);
    }

    /**
     * Permissions granted directly to the subject in the entity (not via a role).
     *
     * @return array<int, string>
     */
    public function directPermissionsInEntity(GuardableResource $entity): array
    {
        $key = $entity->getMorphClass() . ':' . $entity->getKey();

        return $this->entityDirectPermissionCache[$key] ??= DB::table(Config::table('model_has_permissions') . ' as mhp')
            ->join(Config::table('permissions') . ' as p', 'p.id', '=', 'mhp.permission_id')
            ->where('mhp.model_type', $this->getMorphClass())
            ->where('mhp.model_id', (string) $this->getKey())
            ->where('mhp.entity_type', $entity->getMorphClass())
            ->where('mhp.entity_id', (string) $entity->getKey())
            ->pluck('p.value')
            ->all();
    }

    /**
     * Override to exclude this subject from an entity even when a role or direct
     * grant would otherwise allow access (e.g. a user who left a project).
     */
    protected function entityIsDisabled(GuardableResource $entity): bool
    {
        return false;
    }

    /**
     * @return array<int, string>
     */
    protected function roleIdsInEntity(GuardableResource $entity): array
    {
        $key = $entity->getMorphClass() . ':' . $entity->getKey();

        return $this->entityRoleIdCache[$key] ??= $this->modelRoleQuery($entity)->pluck('role_id')->all();
    }

    protected function modelRoleQuery(GuardableResource $entity): Builder
    {
        return ModelHasRole::query()
            ->where('model_type', $this->getMorphClass())
            ->where('model_id', (string) $this->getKey())
            ->where('entity_type', $entity->getMorphClass())
            ->where('entity_id', (string) $entity->getKey());
    }

    protected function modelPermissionQuery(GuardableResource $entity): Builder
    {
        return ModelHasPermission::query()
            ->where('model_type', $this->getMorphClass())
            ->where('model_id', (string) $this->getKey())
            ->where('entity_type', $entity->getMorphClass())
            ->where('entity_id', (string) $entity->getKey());
    }

    private function resolveRoleModel(Role|string|BackedEnum $role, bool $fail = true): ?Role
    {
        if ($role instanceof Role) {
            return $role;
        }

        $value = Config::value($role);
        $found = config('access-guard.models.role', Role::class)::query()->where('value', $value)->first();

        if ($found === null && $fail) {
            throw RoleDoesNotExist::named($value);
        }

        return $found;
    }

    private function resolvePermissionModel(Permission|string|BackedEnum $permission): Permission
    {
        if ($permission instanceof Permission) {
            return $permission;
        }

        return config('access-guard.models.permission', Permission::class)
            ::firstOrCreate(['value' => Config::value($permission)]);
    }

    private function fireGuardEvent(object $event): void
    {
        if (config('access-guard.events_enabled', false)) {
            event($event);
        }
    }
}
