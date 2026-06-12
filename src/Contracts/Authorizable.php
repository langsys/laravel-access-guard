<?php

namespace Langsys\AccessGuard\Contracts;

/**
 * Optional contract for subjects that can bypass all authorization checks.
 * Implement on your user (or any subject) only if your app has super admins.
 */
interface Authorizable
{
    public function isSuperAdmin(): bool;
}
