<?php

namespace Langsys\AccessGuard\Tests\Stubs;

use Langsys\AccessGuard\Contracts\ActsOnBehalfOf;
use Langsys\AccessGuard\Contracts\AuthorizableByKey;

class TestActingApiKey implements AuthorizableByKey, ActsOnBehalfOf
{
    /**
     * @param array<int, string> $permissions
     * @param array<int, int> $entityIds
     */
    public function __construct(
        private array $permissions = [],
        private array $entityIds = [],
        private bool $acting = true,
        private ?object $subject = null,
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

    public function actsForSomeone(): bool
    {
        return $this->acting;
    }

    public function actsOnBehalfOf(): ?object
    {
        return $this->subject;
    }
}
