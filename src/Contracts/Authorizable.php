<?php

namespace Langsys\AccessGuard\Contracts;

/**
 * Optional contract for subjects that can bypass all authorization checks.
 * Implement on your user (or any subject) only if your app has super admins.
 *
 * The one limit: a request made with an API key that acts for someone
 * (ActsOnBehalfOf) is always bounded by that key's own permission and entity
 * checks, even when the request's user or the key's acting subject is a super
 * admin.
 */
interface Authorizable
{
    public function isSuperAdmin(): bool;
}
