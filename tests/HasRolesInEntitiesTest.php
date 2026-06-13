<?php

namespace Langsys\AccessGuard\Tests;

use Langsys\AccessGuard\Models\Role;
use Langsys\AccessGuard\Tests\Models\Project;
use Langsys\AccessGuard\Tests\Models\User;

class HasRolesInEntitiesTest extends TestCase
{
    private function adminRole(): Role
    {
        return Role::create(['value' => 'admin', 'label' => 'Admin'])
            ->grantPermissions(['view_projects', 'edit_projects']);
    }

    public function test_roles_are_scoped_to_the_entity(): void
    {
        $user = User::create([]);
        $projectA = Project::create([]);
        $projectB = Project::create([]);
        $admin = $this->adminRole();

        $user->assignRole($admin, $projectA);

        $this->assertTrue($user->hasRole('admin', $projectA));
        $this->assertFalse($user->hasRole('admin', $projectB));
        $this->assertTrue($user->hasPermissionInEntity('view_projects', $projectA));
        $this->assertFalse($user->hasPermissionInEntity('view_projects', $projectB));
    }

    public function test_multiple_roles_union_their_permissions(): void
    {
        $user = User::create([]);
        $project = Project::create([]);
        $viewer = Role::create(['value' => 'viewer', 'label' => 'Viewer', 'sort_order' => 1])
            ->grantPermissions(['view_projects']);
        $admin = Role::create(['value' => 'admin', 'label' => 'Admin', 'sort_order' => 5])
            ->grantPermissions(['edit_projects']);

        $user->assignRole($viewer, $project)->assignRole($admin, $project);

        $this->assertCount(2, $user->rolesInEntity($project));
        $this->assertSame('admin', $user->roleInEntity($project)->value); // highest sort_order
        $this->assertTrue($user->hasPermissionInEntity('view_projects', $project));
        $this->assertTrue($user->hasPermissionInEntity('edit_projects', $project));
        $this->assertEqualsCanonicalizing(
            ['view_projects', 'edit_projects'],
            $user->permissionsInEntity($project),
        );
    }

    public function test_assigning_is_idempotent(): void
    {
        $user = User::create([]);
        $project = Project::create([]);
        $admin = $this->adminRole();

        $user->assignRole($admin, $project)->assignRole($admin, $project);

        $this->assertCount(1, $user->rolesInEntity($project));
    }

    public function test_remove_and_sync_roles(): void
    {
        $user = User::create([]);
        $project = Project::create([]);
        $admin = $this->adminRole();
        $viewer = Role::create(['value' => 'viewer', 'label' => 'Viewer'])->grantPermissions(['view_projects']);

        $user->assignRole($admin, $project);
        $user->removeRole($admin, $project);
        $this->assertFalse($user->hasRole('admin', $project));

        $user->assignRole($admin, $project);
        $user->syncRoles([$viewer], $project);
        $this->assertFalse($user->hasRole('admin', $project));
        $this->assertTrue($user->hasRole('viewer', $project));
        $this->assertCount(1, $user->rolesInEntity($project));
    }

    public function test_disabled_entity_short_circuits_to_no_permissions(): void
    {
        $user = new class extends User {
            protected $table = 'users';

            protected function entityIsDisabled($entity): bool
            {
                return true;
            }
        };
        $user->save();
        $project = Project::create([]);
        $user->assignRole($this->adminRole(), $project);

        $this->assertFalse($user->hasPermissionInEntity('view_projects', $project));
        $this->assertSame([], $user->permissionsInEntity($project));
    }
}
