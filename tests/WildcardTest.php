<?php

namespace Langsys\AccessGuard\Tests;

use Langsys\AccessGuard\Models\Role;
use Langsys\AccessGuard\Tests\Models\Project;
use Langsys\AccessGuard\Tests\Models\User;

class WildcardTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        $app['config']->set('access-guard.wildcard.enabled', true);
    }

    public function test_a_segment_wildcard_matches_specific_permissions(): void
    {
        $user = User::create([]);
        $project = Project::create([]);
        $role = Role::create(['value' => 'admin', 'label' => 'Admin'])->grantPermissions(['projects.*']);
        $user->assignRole($role, $project);

        $this->assertTrue($user->hasPermissionInEntity('projects.edit', $project));
        $this->assertTrue($user->hasPermissionInEntity('projects.delete', $project));
        $this->assertFalse($user->hasPermissionInEntity('users.edit', $project));
    }

    public function test_the_global_wildcard_matches_everything(): void
    {
        $user = User::create([]);
        $project = Project::create([]);
        $role = Role::create(['value' => 'super', 'label' => 'Super'])->grantPermissions(['*']);
        $user->assignRole($role, $project);

        $this->assertTrue($user->hasPermissionInEntity('anything.at.all', $project));
    }

    public function test_with_wildcard_disabled_matching_is_exact(): void
    {
        config()->set('access-guard.wildcard.enabled', false);

        $user = User::create([]);
        $project = Project::create([]);
        $role = Role::create(['value' => 'admin', 'label' => 'Admin'])->grantPermissions(['projects.*']);
        $user->assignRole($role, $project);

        $this->assertFalse($user->hasPermissionInEntity('projects.edit', $project));
        $this->assertTrue($user->hasPermissionInEntity('projects.*', $project)); // the literal value
    }
}
