<?php

namespace Langsys\AccessGuard\Contracts;

/**
 * Implemented by the user model. Resolves the user's role within a given entity
 * and answers whether that role grants a permission — the entity-scoped half of
 * the RBAC model.
 */
interface AuthorizableByUser
{
    /**
     * The user's role within the entity, or null if they have none.
     */
    public function userRoleInEntity(mixed $entity): ?object;

    /**
     * Whether the given role grants the permission.
     */
    public function roleHasPermission(object $role, string $permission): bool;

    /**
     * Whether the user has opted out of / been excluded from this entity, even
     * if their role would otherwise grant access.
     */
    public function userHasDisabledEntity(mixed $entity): bool;
}
