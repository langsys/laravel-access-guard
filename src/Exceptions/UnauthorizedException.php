<?php

namespace Langsys\AccessGuard\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;

/**
 * Thrown when authorization is denied. Extends Laravel's AuthorizationException
 * so it still renders as a 403 and existing catch blocks keep working, while
 * carrying the permission/entity that was checked.
 */
class UnauthorizedException extends AuthorizationException
{
    public ?string $permission = null;

    public mixed $entity = null;

    public static function forPermission(string $permission, mixed $entity = null): self
    {
        $message = config('access-guard.display_permission_in_exception', false)
            ? "The current subject lacks the [{$permission}] permission for this resource."
            : 'This action is unauthorized.';

        $exception = new self($message);
        $exception->permission = $permission;
        $exception->entity = $entity;

        return $exception;
    }
}
