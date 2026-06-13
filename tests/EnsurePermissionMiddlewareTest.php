<?php

namespace Langsys\AccessGuard\Tests;

use Illuminate\Routing\Middleware\SubstituteBindings;
use Langsys\AccessGuard\Models\Role;
use Langsys\AccessGuard\Tests\Models\Project;
use Langsys\AccessGuard\Tests\Models\User;

class EnsurePermissionMiddlewareTest extends TestCase
{
    protected function defineRoutes($router): void
    {
        $router->bind('project', fn ($id) => Project::findOrFail($id));

        $router->get('/projects/{project}', fn () => response()->json(['ok' => true]))
            ->middleware([SubstituteBindings::class, 'access-guard:view_projects,project']);
    }

    public function test_authorized_user_passes(): void
    {
        $user = User::create([]);
        $project = Project::create([]);
        Role::create(['value' => 'viewer', 'label' => 'Viewer'])->grantPermissions(['view_projects']);
        $user->assignRole('viewer', $project);

        $this->actingAs($user)->getJson("/projects/{$project->id}")->assertOk();
    }

    public function test_unauthorized_user_is_forbidden(): void
    {
        $user = User::create([]);
        $project = Project::create([]);

        $this->actingAs($user)->getJson("/projects/{$project->id}")->assertForbidden();
    }
}
