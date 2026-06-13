<?php

namespace Langsys\AccessGuard\Tests;

use Langsys\AccessGuard\Facades\AccessGuard;
use Langsys\AccessGuard\Models\Role;
use Langsys\AccessGuard\Tests\Models\Project;
use Langsys\AccessGuard\Tests\Models\User;
use Langsys\AccessGuard\Tests\Stubs\Ability;

class EnumAcceptanceTest extends TestCase
{
    public function test_roles_permissions_and_authorization_accept_backed_enums(): void
    {
        $role = Role::create(['value' => 'admin', 'label' => 'Admin'])
            ->grantPermissions([Ability::ViewProjects, Ability::EditProjects]);

        $this->assertTrue($role->hasPermission(Ability::ViewProjects));

        $user = User::create([]);
        $project = Project::create([]);
        $user->assignRole($role, $project);

        AccessGuard::resolveUserUsing(fn () => $user);

        $this->assertTrue(AccessGuard::allows(Ability::ViewProjects, $project));
        $this->assertTrue(AccessGuard::allows(Ability::EditProjects, $project));
    }
}
