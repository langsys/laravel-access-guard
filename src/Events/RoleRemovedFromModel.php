<?php

namespace Langsys\AccessGuard\Events;

use Langsys\AccessGuard\Contracts\GuardableResource;
use Langsys\AccessGuard\Models\Role;

class RoleRemovedFromModel
{
    public function __construct(
        public object $model,
        public Role $role,
        public GuardableResource $entity,
    ) {
    }
}
