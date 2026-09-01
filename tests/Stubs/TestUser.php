<?php

namespace Langsys\AccessGuard\Tests\Stubs;

use Langsys\AccessGuard\Contracts\Authorizable;
use Langsys\AccessGuard\Contracts\AuthorizableByUser;
use Langsys\AccessGuard\Contracts\GrantsPermissions;

class TestUser implements Authorizable, AuthorizableByUser
{
    public function __construct(
        private bool $superAdmin = false,
        private ?TestRole $role = null,
        private bool $disabledEntity = false,
    ) {
    }

    public function isSuperAdmin(): bool
    {
        return $this->superAdmin;
    }

    public function userRoleInEntity(mixed $entity): ?GrantsPermissions
    {
        return $this->role;
    }

    public function userHasDisabledEntity(mixed $entity): bool
    {
        return $this->disabledEntity;
    }
}
