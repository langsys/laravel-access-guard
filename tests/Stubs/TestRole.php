<?php

namespace Langsys\AccessGuard\Tests\Stubs;

class TestRole
{
    /**
     * @param array<int, string> $permissions
     */
    public function __construct(public array $permissions = [])
    {
    }
}
