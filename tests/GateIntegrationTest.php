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

    public function test_policy_keeps_authority_when_ability_is_not_a_held_permission(): void
    {
        Gate::policy(Project::class, ProjectTestPolicy::class);
        $user = User::create([]);
        $project = Project::create([]);

        // The hook abstains (grant-only), so the policy method answers —
        // even though the entity is a GuardableResource.
        $this->assertTrue(Gate::forUser($user)->allows('isProjectAdmin', $project));
        $this->assertTrue(Gate::forUser($user)->denies('isProjectAuditor', $project));
    }

    public function test_held_permission_grants_before_a_denying_policy(): void
    {
        Gate::policy(Project::class, ProjectTestPolicy::class);
        $user = User::create([]);
        $project = Project::create([]);
        $role = Role::create(['value' => 'editor', 'label' => 'Editor'])->grantPermissions(['edit_projects']);
        $user->assignRole($role, $project);

        // Permissions grant: a held permission short-circuits allow even though
        // the policy's edit_projects() would deny.
        $this->assertTrue(Gate::forUser($user)->allows('edit_projects', $project));
    }
}

class ProjectTestPolicy
{
    public function isProjectAdmin(User $user, Project $project): bool
    {
        return true;
    }

    public function isProjectAuditor(User $user, Project $project): bool
    {
        return false;
    }

    public function edit_projects(User $user, Project $project): bool
    {
        return false;
    }
}
