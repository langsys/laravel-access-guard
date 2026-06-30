<?php

namespace Langsys\AccessGuard\Tests;

use Langsys\AccessGuard\Models\Role;
use Langsys\AccessGuard\Tests\Models\Project;
use Langsys\AccessGuard\Tests\Models\User;

class DirectPermissionsTest extends TestCase
{
    public function test_direct_permission_grants_access_without_a_role(): void
    {
        $user = User::create([]);
        $project = Project::create([]);

        $user->givePermission('export_data', $project);

        $this->assertTrue($user->hasPermissionInEntity('export_data', $project));
        $this->assertContains('export_data', $user->directPermissionsInEntity($project));
        $this->assertContains('export_data', $user->permissionsInEntity($project));
    }

    public function test_direct_permission_is_entity_scoped(): void
    {
        $user = User::create([]);
        $a = Project::create([]);
        $b = Project::create([]);

        $user->givePermission('export_data', $a);

        $this->assertTrue($user->hasPermissionInEntity('export_data', $a));
        $this->assertFalse($user->hasPermissionInEntity('export_data', $b));
    }

    public function test_revoking_a_direct_permission(): void
    {
        $user = User::create([]);
        $project = Project::create([]);

        $user->givePermission('export_data', $project);
        $user->revokePermission('export_data', $project);

        $this->assertFalse($user->hasPermissionInEntity('export_data', $project));
    }

    public function test_permissions_in_entity_union_roles_and_direct_grants(): void
    {
        $user = User::create([]);
        $project = Project::create([]);
        $role = Role::create(['value' => 'viewer', 'label' => 'Viewer'])->grantPermissions(['view_projects']);

        $user->assignRole($role, $project);
        $user->givePermission('export_data', $project);

        $this->assertEqualsCanonicalizing(
            ['view_projects', 'export_data'],
            $user->permissionsInEntity($project),
        );
    }
}
