<?php

namespace Langsys\AccessGuard\Tests\Stubs;

use Langsys\AccessGuard\Contracts\GuardableResource;

class TestEntity implements GuardableResource
{
    public function __construct(public int $id = 1)
    {
    }
}
