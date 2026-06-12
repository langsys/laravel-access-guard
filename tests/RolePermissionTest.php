<?php

namespace Langsys\AccessGuard\Tests;

use Langsys\AccessGuard\Models\Permission;
use Langsys\AccessGuard\Models\Role;

class RolePermissionTest extends TestCase
{
    public function test_role_resolves_permissions_via_the_pivot(): void
    {
        $role = Role::create(['value' => 'project_admin', 'label' => 'Project Admin']);
        $permission = Permission::create(['value' => 'view_projects']);
        $role->permissions()->attach($permission);

        $this->assertTrue($role->hasPermission('view_projects'));
        $this->assertTrue($role->hasPermission($permission));
        $this->assertFalse($role->hasPermission('edit_projects'));
    }

    public function test_grant_permissions_creates_and_attaches_by_value(): void
    {
        $role = Role::create(['value' => 'project_admin', 'label' => 'Project Admin']);

        $role->grantPermissions(['view_projects', 'edit_projects']);

        $this->assertTrue($role->hasPermission('view_projects'));
        $this->assertTrue($role->hasPermission('edit_projects'));
        $this->assertCount(2, Permission::all());

        // Idempotent — re-granting does not duplicate the pivot row.
        $role->grantPermissions('view_projects');
        $this->assertCount(2, $role->permissions()->get());
    }

    public function test_models_use_uuid_primary_keys(): void
    {
        $role = Role::create(['value' => 'project_admin', 'label' => 'Project Admin']);

        $this->assertFalse($role->getIncrementing());
        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', $role->id);
    }
}
