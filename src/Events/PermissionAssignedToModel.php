<?php

namespace Langsys\AccessGuard\Events;

use Langsys\AccessGuard\Contracts\GuardableResource;
use Langsys\AccessGuard\Models\Permission;

class PermissionAssignedToModel
{
    public function __construct(
        public object $model,
        public Permission $permission,
        public GuardableResource $entity,
    ) {
    }
}
