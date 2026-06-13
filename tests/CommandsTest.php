<?php

namespace Langsys\AccessGuard\Tests;

use Langsys\AccessGuard\Models\Role;

class CommandsTest extends TestCase
{
    public function test_create_permission_command(): void
    {
        $this->artisan('access-guard:create-permission', ['value' => 'view_projects', 'label' => 'View'])
            ->assertSuccessful();

        $this->assertDatabaseHas('permissions', ['value' => 'view_projects', 'label' => 'View']);
    }

    public function test_create_role_command_grants_permissions(): void
    {
        $this->artisan('access-guard:create-role', [
            'value' => 'admin',
            'label' => 'Admin',
            '--permissions' => 'view_projects,edit_projects',
        ])->assertSuccessful();

        $role = Role::where('value', 'admin')->first();

        $this->assertNotNull($role);
        $this->assertTrue($role->hasPermission('view_projects'));
        $this->assertTrue($role->hasPermission('edit_projects'));
    }

    public function test_show_command_runs(): void
    {
        Role::create(['value' => 'admin', 'label' => 'Admin'])->grantPermissions(['view_projects']);

        $this->artisan('access-guard:show')->assertSuccessful();
    }

    public function test_cache_reset_command_runs(): void
    {
        $this->artisan('access-guard:cache-reset')->assertSuccessful();
    }
}
