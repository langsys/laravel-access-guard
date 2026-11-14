<?php

namespace Langsys\AccessGuard\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Langsys\AccessGuard\Bridge\ApiKeyAuthorizable;
use Langsys\AccessGuard\Contracts\GuardableResource;
use Langsys\AccessGuard\Contracts\HasGuardableParent;
use Langsys\AccessGuard\Facades\AccessGuard;
use Langsys\AccessGuard\Tests\Models\FakeApiKey;
use Langsys\AccessGuard\Tests\Models\Organization;
use Langsys\AccessGuard\Tests\Models\OrganizationProject;

class EntityParentLinkTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('access-guard.api_key.bridge', FakeApiKey::class);
    }

    protected function defineDatabaseMigrations(): void
    {
        parent::defineDatabaseMigrations();

        Schema::create('fake_api_keys', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->json('permissions');
        });

        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id')->nullable();
        });

        Schema::create('organization_projects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
        });
    }

    private function key(array $permissions = ['edit_projects']): FakeApiKey
    {
        return FakeApiKey::create(['id' => 'key-1', 'permissions' => $permissions]);
    }

    private function actAs(FakeApiKey $key): void
    {
        AccessGuard::resolveApiKeyUsing(fn () => $key);
    }

    public function test_a_key_linked_to_an_organization_reaches_its_projects(): void
    {
        $organization = Organization::create([]);
        $project = OrganizationProject::create(['organization_id' => $organization->id]);
        $key = $this->key();
        $organization->grantApiKey($key);

        $this->actAs($key);

        $this->assertTrue(AccessGuard::allows('edit_projects', $project));
        $this->assertTrue(AccessGuard::allows('edit_projects', $organization));
    }

    public function test_a_key_linked_to_an_organization_reaches_a_project_created_after_the_link(): void
    {
        $organization = Organization::create([]);
        $key = $this->key();
        $organization->grantApiKey($key);

        $project = OrganizationProject::create(['organization_id' => $organization->id]);

        $this->actAs($key);

        $this->assertTrue(AccessGuard::allows('edit_projects', $project));
    }

    public function test_a_key_linked_to_a_project_does_not_reach_its_organization(): void
    {
        $organization = Organization::create([]);
        $project = OrganizationProject::create(['organization_id' => $organization->id]);
        $key = $this->key();
        $project->grantApiKey($key);

        $this->actAs($key);

        $this->assertTrue(AccessGuard::allows('edit_projects', $project));
        $this->assertTrue(AccessGuard::denies('edit_projects', $organization));
    }

    public function test_a_key_linked_to_a_project_does_not_reach_a_sibling_project(): void
    {
        $organization = Organization::create([]);
        $project = OrganizationProject::create(['organization_id' => $organization->id]);
        $sibling = OrganizationProject::create(['organization_id' => $organization->id]);
        $key = $this->key();
        $project->grantApiKey($key);

        $this->actAs($key);

        $this->assertTrue(AccessGuard::denies('edit_projects', $sibling));
    }

    public function test_a_key_linked_to_another_organization_does_not_reach_the_project(): void
    {
        $organization = Organization::create([]);
        $other = Organization::create([]);
        $project = OrganizationProject::create(['organization_id' => $organization->id]);
        $key = $this->key();
        $other->grantApiKey($key);

        $this->actAs($key);

        $this->assertTrue(AccessGuard::denies('edit_projects', $project));
    }

    public function test_a_link_matches_the_entity_type_and_id_together(): void
    {
        // Organization 1 and project 1 share an id. A link to organization 1
        // must not reach project 1, whose own organization is 2.
        $linked = Organization::create([]);
        $owner = Organization::create([]);
        $project = OrganizationProject::create(['organization_id' => $owner->id]);
        $this->assertSame($linked->id, $project->id);

        $key = $this->key();
        $linked->grantApiKey($key);

        $this->actAs($key);

        $this->assertTrue(AccessGuard::denies('edit_projects', $project));
    }

    public function test_a_link_reaches_through_every_level_above(): void
    {
        $top = Organization::create([]);
        $middle = Organization::create(['parent_id' => $top->id]);
        $project = OrganizationProject::create(['organization_id' => $middle->id]);
        $key = $this->key();
        $top->grantApiKey($key);

        $this->actAs($key);

        $this->assertTrue(AccessGuard::allows('edit_projects', $project));
        $this->assertTrue(AccessGuard::allows('edit_projects', $middle));
    }

    public function test_a_parent_cycle_ends_the_walk(): void
    {
        $a = Organization::create([]);
        $b = Organization::create(['parent_id' => $a->id]);
        $a->update(['parent_id' => $b->id]);
        $unlinked = Organization::create([]);
        $key = $this->key();
        $unlinked->grantApiKey($key);

        $this->actAs($key);

        $this->assertTrue(AccessGuard::denies('edit_projects', $a));

        $b->grantApiKey($key);

        $this->assertTrue(AccessGuard::allows('edit_projects', $a->fresh()));
    }

    public function test_a_parent_that_is_not_a_model_is_walked_through(): void
    {
        $organization = Organization::create([]);
        $key = $this->key();
        $organization->grantApiKey($key);

        $middle = $this->plainEntity($organization);
        $entity = $this->plainEntity($middle);

        $this->actAs($key);

        $this->assertTrue(AccessGuard::allows('edit_projects', $entity));
        $this->assertTrue(AccessGuard::denies('edit_projects', $this->plainEntity(null)));
    }

    public function test_the_lookup_is_one_query_whatever_the_depth(): void
    {
        $top = Organization::create([]);
        $middle = Organization::create(['parent_id' => $top->id]);
        $project = OrganizationProject::create(['organization_id' => $middle->id]);
        $key = $this->key();
        $top->grantApiKey($key);

        $this->actAs($key);

        DB::enableQueryLog();
        AccessGuard::allows('edit_projects', $project);
        $lookups = collect(DB::getQueryLog())
            ->filter(fn (array $query) => str_contains($query['query'], 'entity_has_api_keys'));

        $this->assertCount(1, $lookups);
    }

    public function test_a_key_model_that_authorizes_itself_can_reuse_the_lookup(): void
    {
        $organization = Organization::create([]);
        $project = OrganizationProject::create(['organization_id' => $organization->id]);
        $key = $this->key();
        $organization->grantApiKey($key);

        $bridge = new ApiKeyAuthorizable($key);

        $this->assertTrue($bridge->keyBelongsToEntity($project));
        $this->assertFalse($bridge->keyBelongsToEntity(OrganizationProject::create(['organization_id' => 99])));
    }

    private function plainEntity(?GuardableResource $parent): GuardableResource
    {
        return new class ($parent) implements GuardableResource, HasGuardableParent {
            public function __construct(private ?GuardableResource $parent)
            {
            }

            public function guardableParent(): ?GuardableResource
            {
                return $this->parent;
            }
        };
    }
}
