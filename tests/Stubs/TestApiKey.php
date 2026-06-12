<?php

namespace Langsys\AccessGuard\Tests\Stubs;

use Langsys\AccessGuard\Contracts\AuthorizableByKey;

class TestApiKey implements AuthorizableByKey
{
    /**
     * @param array<int, string> $permissions
     * @param array<int, int> $entityIds
     */
    public function __construct(
        private array $permissions = [],
        private array $entityIds = [],
    ) {
    }

    public function keyHasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions, true);
    }

    public function keyBelongsToEntity(mixed $entity): bool
    {
        return in_array($entity->id, $this->entityIds, true);
    }
}
