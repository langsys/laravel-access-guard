<?php

namespace Langsys\AccessGuard\Concerns;

use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Langsys\AccessGuard\Contracts\GuardableResource;
use Langsys\AccessGuard\Events\RoleAssignedToModel;
use Langsys\AccessGuard\Events\RoleRemovedFromModel;
use Langsys\AccessGuard\Models\ModelHasRole;
use Langsys\AccessGuard\Models\Role;
use Langsys\AccessGuard\PermissionRegistrar;
use Langsys\AccessGuard\Support\Config;

/**
 * Batteries-included entity-scoped role assignment. Put this on your user model
 * (or any subject) to get assign/remove/sync/has plus the AuthorizableInEntity
 * permission check, backed by the model_has_roles pivot.
 *
 * @see \Langsys\AccessGuard\Contracts\AuthorizableInEntity
 */
trait HasRolesInEntities
{
    /** @var array<string, array<int, string>> */
    protected array $entityRoleIdCache = [];

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
        $registrar = app(PermissionRegistrar::class);

        foreach ($this->roleIdsInEntity($entity) as $roleId) {
            if ($registrar->roleHasPermission($roleId, $value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Every permission value the subject holds in the entity (union of its roles).
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

        return array_keys($values);
    }

    /**
     * Override to exclude this subject from an entity even when a role would grant
     * access (e.g. a user who muted/left a project).
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

    private function resolveRoleModel(Role|string|BackedEnum $role, bool $fail = true): ?Role
    {
        if ($role instanceof Role) {
            return $role;
        }

        $model = config('access-guard.models.role', Role::class);
        $query = $model::query()->where('value', Config::value($role));

        return $fail ? $query->firstOrFail() : $query->first();
    }

    private function fireGuardEvent(object $event): void
    {
        if (config('access-guard.events_enabled', false)) {
            event($event);
        }
    }
}
