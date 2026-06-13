<?php

namespace Langsys\AccessGuard\Tests;

use Illuminate\Support\Facades\Gate;
use Langsys\AccessGuard\Models\Role;
use Langsys\AccessGuard\Tests\Models\Project;
use Langsys\AccessGuard\Tests\Models\User;

class GateIntegrationTest extends TestCase
{
    public function test_can_routes_through_access_guard_for_entities(): void
    {
        $user = User::create([]);
        $project = Project::create([]);
        $role = Role::create(['value' => 'admin', 'label' => 'Admin'])->grantPermissions(['edit_projects']);
        $user->assignRole($role, $project);

        $this->assertTrue(Gate::forUser($user)->allows('edit_projects', $project));
        $this->assertTrue(Gate::forUser($user)->denies('delete_projects', $project));
    }

    public function test_super_admin_passes_every_gate_check(): void
    {
        $user = User::create(['is_super' => true]);
        $project = Project::create([]);

        $this->assertTrue(Gate::forUser($user)->allows('anything', $project));
        $this->assertTrue(Gate::forUser($user)->allows('whatever')); // even without an entity
    }

    public function test_gate_defers_for_non_entity_abilities(): void
    {
        Gate::define('ping', fn ($user) => true);
        $user = User::create([]);

        $this->assertTrue(Gate::forUser($user)->allows('ping'));
    }
}
