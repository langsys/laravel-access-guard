<?php

namespace Langsys\AccessGuard\Contracts;

/**
 * Implemented by the role model — whatever userRoleInEntity() returns. The role
 * answers for itself whether it grants a permission, so user models don't have
 * to carry role knowledge.
 */
interface GrantsPermissions
{
    public function hasPermission(string $permission): bool;
}
