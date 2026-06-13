<?php

namespace Langsys\AccessGuard\Concerns;

use Langsys\AccessGuard\PermissionRegistrar;

trait RefreshesPermissionCache
{
    public static function bootRefreshesPermissionCache(): void
    {
        $flush = fn () => app(PermissionRegistrar::class)->forgetCachedPermissions();

        static::saved($flush);
        static::deleted($flush);
    }
}
