<?php

namespace Langsys\AccessGuard\Events;

use Langsys\AccessGuard\Models\Permission;
use Langsys\AccessGuard\Models\Role;

class PermissionAssignedToRole
{
    public function __construct(
        public Role $role,
        public Permission $permission,
    ) {
    }
}
