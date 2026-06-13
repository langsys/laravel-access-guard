<?php

namespace Langsys\AccessGuard\Tests;

use Illuminate\Support\Facades\DB;
use Langsys\AccessGuard\Models\Role;
use Langsys\AccessGuard\PermissionRegistrar;

class CachingTest extends TestCase
{
    private function mapQueryCount(): int
    {
        return collect(DB::getQueryLog())
            ->filter(fn ($q) => str_contains($q['query'], 'role_has_permissions'))
            ->count();
    }

    public function test_permission_map_loads_from_the_database_only_once(): void
    {
        $role = Role::create(['value' => 'admin', 'label' => 'Admin'])
            ->grantPermissions(['view_projects', 'edit_projects']);

        DB::connection()->enableQueryLog();

        $role->hasPermission('view_projects');
        $role->hasPermission('edit_projects');
        $role->hasPermission('view_projects');

        $this->assertSame(1, $this->mapQueryCount());
    }

    public function test_cache_reset_forces_a_reload(): void
    {
        $role = Role::create(['value' => 'admin', 'label' => 'Admin'])->grantPermissions(['view_projects']);
        $role->hasPermission('view_projects'); // warm the cache

        DB::connection()->enableQueryLog();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $role->hasPermission('view_projects');

        $this->assertSame(1, $this->mapQueryCount());
    }
}
