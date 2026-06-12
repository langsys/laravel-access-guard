<?php

namespace Langsys\AccessGuard\Contracts;

/**
 * Implemented by the API key model. A key authorizes a request when it both
 * holds the permission and is linked to the entity being accessed.
 */
interface AuthorizableByKey
{
    public function keyHasPermission(string $permission): bool;

    public function keyBelongsToEntity(mixed $entity): bool;
}
