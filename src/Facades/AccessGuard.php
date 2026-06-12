<?php

namespace Langsys\AccessGuard\Facades;

use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use Langsys\AccessGuard\AccessGuardService;
use Langsys\AccessGuard\Contracts\GuardableResource;

/**
 * @method static void authorize(string $permission, ?GuardableResource $entity)
 * @method static bool allows(string $permission, ?GuardableResource $entity)
 * @method static bool denies(string $permission, ?GuardableResource $entity)
 * @method static Collection filterByPermission(string $permission, Collection $collection)
 * @method static AccessGuardService resolveUserUsing(Closure $resolver)
 * @method static AccessGuardService resolveApiKeyUsing(Closure $resolver)
 *
 * @see AccessGuardService
 */
class AccessGuard extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'access-guard';
    }
}
