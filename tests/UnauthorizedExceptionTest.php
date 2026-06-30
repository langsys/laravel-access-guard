<?php

namespace Langsys\AccessGuard\Tests;

use Illuminate\Auth\Access\AuthorizationException;
use Langsys\AccessGuard\Exceptions\UnauthorizedException;
use Langsys\AccessGuard\Facades\AccessGuard;
use Langsys\AccessGuard\Tests\Models\Project;
use Langsys\AccessGuard\Tests\Models\User;

class UnauthorizedExceptionTest extends TestCase
{
    public function test_denial_throws_unauthorized_exception_carrying_context(): void
    {
        $user = User::create([]);
        $project = Project::create([]);
        AccessGuard::resolveUserUsing(fn () => $user);

        try {
            AccessGuard::authorize('edit_projects', $project);
            $this->fail('Expected UnauthorizedException.');
        } catch (UnauthorizedException $e) {
            $this->assertInstanceOf(AuthorizationException::class, $e); // stays a 403
            $this->assertSame('edit_projects', $e->permission);
            $this->assertTrue($e->entity->is($project));
        }
    }

    public function test_permission_is_hidden_in_the_message_by_default(): void
    {
        $user = User::create([]);
        $project = Project::create([]);
        AccessGuard::resolveUserUsing(fn () => $user);

        try {
            AccessGuard::authorize('edit_projects', $project);
        } catch (UnauthorizedException $e) {
            $this->assertStringNotContainsString('edit_projects', $e->getMessage());
        }
    }

    public function test_permission_is_shown_when_configured(): void
    {
        config()->set('access-guard.display_permission_in_exception', true);
        $user = User::create([]);
        $project = Project::create([]);
        AccessGuard::resolveUserUsing(fn () => $user);

        try {
            AccessGuard::authorize('edit_projects', $project);
        } catch (UnauthorizedException $e) {
            $this->assertStringContainsString('edit_projects', $e->getMessage());
        }
    }
}
