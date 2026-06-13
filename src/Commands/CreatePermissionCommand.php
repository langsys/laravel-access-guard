<?php

namespace Langsys\AccessGuard\Commands;

use Illuminate\Console\Command;

class CreatePermissionCommand extends Command
{
    protected $signature = 'access-guard:create-permission {value} {label?}';

    protected $description = 'Create a permission';

    public function handle(): int
    {
        $model = config('access-guard.models.permission');

        $permission = $model::firstOrCreate(
            ['value' => $this->argument('value')],
            ['label' => $this->argument('label')],
        );

        $this->info("Permission `{$permission->value}` ready ({$permission->getKey()}).");

        return self::SUCCESS;
    }
}
