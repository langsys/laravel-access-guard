<?php

namespace Langsys\AccessGuard\Contracts;

/**
 * Optional contract for subjects that can bypass all authorization checks.
 * Implement on your user (or any subject) only if your app has super admins.
 *
 * The one limit: in AccessGuard's own checks (authorize, allows,
 * filterByPermission, the middleware), a request made with an API key that acts
 * for someone (ActsOnBehalfOf) is bounded by that key's own permission and
 * entity checks, even when the request's user or the key's acting subject is a
 * super admin. The Gate hook sees only the Gate user and doesn't apply it.
 */
interface Authorizable
{
    public function isSuperAdmin(): bool;
}
