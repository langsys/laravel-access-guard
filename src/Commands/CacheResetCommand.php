<?php

namespace Langsys\AccessGuard\Commands;

use Illuminate\Console\Command;
use Langsys\AccessGuard\PermissionRegistrar;

class CacheResetCommand extends Command
{
    protected $signature = 'access-guard:cache-reset';

    protected $description = 'Flush the cached role/permission map';

    public function handle(PermissionRegistrar $registrar): int
    {
        $registrar->forgetCachedPermissions();

        $this->info('Access Guard permission cache flushed.');

        return self::SUCCESS;
    }
}
