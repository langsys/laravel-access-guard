<?php

namespace Langsys\AccessGuard\Contracts;

use BackedEnum;

/**
 * Richer alternative to AuthorizableByUser. A subject answers directly whether
 * it holds a permission within an entity — letting it union multiple roles (and
 * direct grants) instead of resolving to a single role. Implemented for free by
 * the HasRolesInEntities trait.
 */
interface AuthorizableInEntity
{
    public function hasPermissionInEntity(string|BackedEnum $permission, mixed $entity): bool;
}
