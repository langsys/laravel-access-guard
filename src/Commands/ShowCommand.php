<?php

namespace Langsys\AccessGuard\Commands;

use Illuminate\Console\Command;

class ShowCommand extends Command
{
    protected $signature = 'access-guard:show';

    protected $description = 'List roles and the permissions they grant';

    public function handle(): int
    {
        $roleModel = config('access-guard.models.role');

        $roles = $roleModel::with('permissions')->orderBy('sort_order')->get();

        if ($roles->isEmpty()) {
            $this->warn('No roles defined yet.');

            return self::SUCCESS;
        }

        $this->table(
            ['Role', 'Label', 'Permissions'],
            $roles->map(fn ($role) => [
                $role->value,
                $role->label,
                $role->permissions->pluck('value')->sort()->implode(', ') ?: '—',
            ])->all(),
        );

        return self::SUCCESS;
    }
}
