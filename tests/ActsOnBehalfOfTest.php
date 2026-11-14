<?php

namespace Langsys\AccessGuard\Tests;

use Illuminate\Support\Collection;
use Langsys\AccessGuard\Facades\AccessGuard;
use Langsys\AccessGuard\Tests\Stubs\TestActingApiKey;
use Langsys\AccessGuard\Tests\Stubs\TestApiKey;
use Langsys\AccessGuard\Tests\Stubs\TestEntity;
use Langsys\AccessGuard\Tests\Stubs\TestEntityScopedUser;
use Langsys\AccessGuard\Tests\Stubs\TestRole;
use Langsys\AccessGuard\Tests\Stubs\TestUser;

class ActsOnBehalfOfTest extends TestCase
{
    private function useKey(object $key, ?object $user = null): void
    {
        AccessGuard::resolveApiKeyUsing(fn () => $key);
        AccessGuard::resolveUserUsing(fn () => $user);
    }

    private function keyFor(?object $subject, array $permissions = ['view_projects'], array $entityIds = [7]): TestActingApiKey
    {
        return new TestActingApiKey(permissions: $permissions, entityIds: $entityIds, acting: true, subject: $subject);
    }

    private function member(array $permissions = ['view_projects'], bool $disabledEntity = false): TestUser
    {
        return new TestUser(role: new TestRole($permissions), disabledEntity: $disabledEntity);
    }

    public function test_a_key_and_its_subject_both_holding_the_permission_is_allowed(): void
    {
        $this->useKey($this->keyFor($this->member()));

        $this->assertTrue(AccessGuard::allows('view_projects', new TestEntity(7)));
    }

    public function test_a_subject_lacking_the_permission_denies_the_key(): void
    {
        $this->useKey($this->keyFor($this->member(['edit_projects'])));

        $this->assertTrue(AccessGuard::denies('view_projects', new TestEntity(7)));
    }

    public function test_a_subject_with_no_role_in_the_entity_denies_the_key(): void
    {
        $this->useKey($this->keyFor(new TestUser(role: null)));

        $this->assertTrue(AccessGuard::denies('view_projects', new TestEntity(7)));
    }

    public function test_a_subject_who_disabled_the_entity_denies_the_key(): void
    {
        $this->useKey($this->keyFor($this->member(disabledEntity: true)));

        $this->assertTrue(AccessGuard::denies('view_projects', new TestEntity(7)));
    }

    public function test_a_key_acting_for_someone_with_no_subject_is_denied(): void
    {
        $this->useKey($this->keyFor(null));

        $this->assertTrue(AccessGuard::denies('view_projects', new TestEntity(7)));
    }

    public function test_a_subject_that_is_not_a_user_subject_denies_the_key(): void
    {
        $other = new TestApiKey(permissions: ['view_projects'], entityIds: [7]);

        $this->useKey($this->keyFor($other));

        $this->assertTrue(AccessGuard::denies('view_projects', new TestEntity(7)));
    }

    public function test_a_subject_that_answers_per_entity_is_checked_on_the_same_entity(): void
    {
        $subject = new TestEntityScopedUser([7 => ['view_projects'], 8 => []]);

        $this->useKey($this->keyFor($subject, entityIds: [7, 8]));

        $this->assertTrue(AccessGuard::allows('view_projects', new TestEntity(7)));
        $this->assertTrue(AccessGuard::denies('view_projects', new TestEntity(8)));
    }

    public function test_the_subject_cannot_widen_the_key(): void
    {
        $owner = $this->member(['view_projects', 'edit_projects']);

        // The key holds only view_projects and is linked only to entity 7.
        $this->useKey($this->keyFor($owner));

        $this->assertTrue(AccessGuard::denies('edit_projects', new TestEntity(7)));
        $this->assertTrue(AccessGuard::denies('view_projects', new TestEntity(8)));
    }

    public function test_a_super_admin_subject_passes_its_half_only(): void
    {
        $admin = new TestUser(superAdmin: true);

        $this->useKey($this->keyFor($admin));
        $this->assertTrue(AccessGuard::allows('view_projects', new TestEntity(7)));
        $this->assertTrue(AccessGuard::denies('edit_projects', new TestEntity(7)));
        $this->assertTrue(AccessGuard::denies('view_projects', new TestEntity(8)));
    }

    public function test_a_super_admin_request_user_does_not_lift_the_key_checks(): void
    {
        $admin = new TestUser(superAdmin: true);

        $this->useKey($this->keyFor($admin), user: $admin);

        $this->assertTrue(AccessGuard::denies('edit_projects', new TestEntity(7)));
        $this->assertTrue(AccessGuard::denies('view_projects', new TestEntity(8)));
        $this->assertTrue(AccessGuard::denies('view_projects', null));
    }

    public function test_a_super_admin_request_user_does_not_lift_the_subject_check(): void
    {
        $this->useKey($this->keyFor($this->member(['edit_projects'])), user: new TestUser(superAdmin: true));

        $this->assertTrue(AccessGuard::denies('view_projects', new TestEntity(7)));
    }

    public function test_a_key_with_the_contract_that_acts_for_nobody_is_checked_as_before(): void
    {
        $key = new TestActingApiKey(permissions: ['view_projects'], entityIds: [7], acting: false);

        $this->useKey($key);
        $this->assertTrue(AccessGuard::allows('view_projects', new TestEntity(7)));
        $this->assertTrue(AccessGuard::denies('view_projects', new TestEntity(8)));

        // ...including the super-admin shortcut for the request's user.
        $this->useKey($key, user: new TestUser(superAdmin: true));
        $this->assertTrue(AccessGuard::allows('view_projects', new TestEntity(8)));
    }

    public function test_a_key_without_the_contract_keeps_the_super_admin_shortcut(): void
    {
        $this->useKey(new TestApiKey(permissions: [], entityIds: []), user: new TestUser(superAdmin: true));

        $this->assertTrue(AccessGuard::allows('view_projects', new TestEntity(7)));
    }

    public function test_filter_by_permission_applies_both_halves(): void
    {
        $subject = new TestEntityScopedUser([7 => ['view_projects'], 8 => ['view_projects']]);

        // Linked to 7 and 9; the subject holds the permission in 7 and 8.
        $this->useKey($this->keyFor($subject, entityIds: [7, 9]));

        $visible = AccessGuard::filterByPermission(
            'view_projects',
            new Collection([new TestEntity(7), new TestEntity(8), new TestEntity(9)]),
        );

        $this->assertSame([7], $visible->pluck('id')->values()->all());
    }
}
