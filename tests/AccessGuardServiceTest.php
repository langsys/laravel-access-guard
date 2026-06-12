<?php

namespace Langsys\AccessGuard\Tests;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Langsys\AccessGuard\Facades\AccessGuard;
use Langsys\AccessGuard\Tests\Stubs\TestApiKey;
use Langsys\AccessGuard\Tests\Stubs\TestEntity;
use Langsys\AccessGuard\Tests\Stubs\TestRole;
use Langsys\AccessGuard\Tests\Stubs\TestUser;

class AccessGuardServiceTest extends TestCase
{
    public function test_super_admin_bypasses_all_checks(): void
    {
        AccessGuard::resolveUserUsing(fn () => new TestUser(superAdmin: true));

        $this->assertTrue(AccessGuard::allows('anything', new TestEntity()));
        // Super admins are allowed even without an entity.
        $this->assertTrue(AccessGuard::allows('anything', null));
    }

    public function test_user_with_role_granting_permission_is_allowed(): void
    {
        AccessGuard::resolveUserUsing(fn () => new TestUser(role: new TestRole(['view_projects'])));

        $this->assertTrue(AccessGuard::allows('view_projects', new TestEntity()));
    }

    public function test_user_without_required_permission_is_denied(): void
    {
        AccessGuard::resolveUserUsing(fn () => new TestUser(role: new TestRole(['view_projects'])));

        $this->assertTrue(AccessGuard::denies('edit_projects', new TestEntity()));
    }

    public function test_user_with_no_role_in_entity_is_denied(): void
    {
        AccessGuard::resolveUserUsing(fn () => new TestUser(role: null));

        $this->assertTrue(AccessGuard::denies('view_projects', new TestEntity()));
    }

    public function test_user_who_disabled_the_entity_is_denied(): void
    {
        AccessGuard::resolveUserUsing(fn () => new TestUser(
            role: new TestRole(['view_projects']),
            disabledEntity: true,
        ));

        $this->assertTrue(AccessGuard::denies('view_projects', new TestEntity()));
    }

    public function test_authorize_throws_authorization_exception_when_denied(): void
    {
        AccessGuard::resolveUserUsing(fn () => null);

        $this->expectException(AuthorizationException::class);

        AccessGuard::authorize('view_projects', new TestEntity());
    }

    public function test_api_key_with_permission_and_entity_link_is_allowed(): void
    {
        AccessGuard::resolveApiKeyUsing(fn () => new TestApiKey(
            permissions: ['view_projects'],
            entityIds: [7],
        ));

        $this->assertTrue(AccessGuard::allows('view_projects', new TestEntity(7)));
    }

    public function test_api_key_without_permission_is_denied(): void
    {
        AccessGuard::resolveApiKeyUsing(fn () => new TestApiKey(permissions: [], entityIds: [7]));

        $this->assertTrue(AccessGuard::denies('view_projects', new TestEntity(7)));
    }

    public function test_api_key_not_linked_to_entity_is_denied(): void
    {
        AccessGuard::resolveApiKeyUsing(fn () => new TestApiKey(
            permissions: ['view_projects'],
            entityIds: [99],
        ));

        $this->assertTrue(AccessGuard::denies('view_projects', new TestEntity(7)));
    }

    public function test_api_key_takes_precedence_over_user(): void
    {
        // The user would be allowed, but a denied key is present on the request.
        AccessGuard::resolveUserUsing(fn () => new TestUser(role: new TestRole(['view_projects'])));
        AccessGuard::resolveApiKeyUsing(fn () => new TestApiKey(permissions: [], entityIds: []));

        $this->assertTrue(AccessGuard::denies('view_projects', new TestEntity()));
    }

    public function test_filter_by_permission_returns_only_authorized_entities(): void
    {
        AccessGuard::resolveApiKeyUsing(fn () => new TestApiKey(
            permissions: ['view_projects'],
            entityIds: [1, 3],
        ));

        $filtered = AccessGuard::filterByPermission('view_projects', new Collection([
            new TestEntity(1),
            new TestEntity(2),
            new TestEntity(3),
        ]));

        $this->assertEqualsCanonicalizing([1, 3], $filtered->map(fn ($e) => $e->id)->values()->all());
    }
}
