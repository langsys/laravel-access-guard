<?php

namespace Langsys\AccessGuard\Commands;

use Illuminate\Console\Command;

class CreateRoleCommand extends Command
{
    protected $signature = 'access-guard:create-role {value} {label?} {--permissions= : Comma-separated permission values to grant}';

    protected $description = 'Create a role and optionally grant it permissions';

    public function handle(): int
    {
        $model = config('access-guard.models.role');

        $role = $model::firstOrCreate(
            ['value' => $this->argument('value')],
            ['label' => $this->argument('label') ?? $this->argument('value')],
        );

        if ($permissions = $this->option('permissions')) {
            $role->grantPermissions(array_filter(array_map('trim', explode(',', $permissions))));
        }

        $this->info("Role `{$role->value}` ready ({$role->getKey()}).");

        return self::SUCCESS;
    }
}
