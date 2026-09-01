<?php

namespace Langsys\AccessGuard\Tests\Stubs;

use Langsys\AccessGuard\Contracts\GrantsPermissions;

class TestRole implements GrantsPermissions
{
    /**
     * @param array<int, string> $permissions
     */
    public function __construct(public array $permissions = [])
    {
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions, true);
    }
}
