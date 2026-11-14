<?php

namespace Langsys\AccessGuard\Tests\Stubs;

use BackedEnum;
use Langsys\AccessGuard\Contracts\AuthorizableInEntity;

/**
 * A subject that answers per entity itself, the way HasRolesInEntities does.
 */
class TestEntityScopedUser implements AuthorizableInEntity
{
    /**
     * @param array<int, array<int, string>> $permissionsByEntityId
     */
    public function __construct(private array $permissionsByEntityId = [])
    {
    }

    public function hasPermissionInEntity(string|BackedEnum $permission, mixed $entity): bool
    {
        $value = $permission instanceof BackedEnum ? (string) $permission->value : $permission;

        return in_array($value, $this->permissionsByEntityId[$entity->id] ?? [], true);
    }
}
