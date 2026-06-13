<?php

namespace Langsys\AccessGuard\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Langsys\AccessGuard\Facades\AccessGuard;
use Langsys\AccessGuard\Tests\Models\FakeApiKey;
use Langsys\AccessGuard\Tests\Models\Project;

class ApiKeyBridgeTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        // Pretend laravel-api-keys is installed: adapt its key class automatically.
        $app['config']->set('access-guard.api_key.bridge', FakeApiKey::class);
    }

    protected function defineDatabaseMigrations(): void
    {
        parent::defineDatabaseMigrations();

        Schema::create('fake_api_keys', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->json('permissions');
        });
    }

    private function key(string $id, array $permissions): FakeApiKey
    {
        return FakeApiKey::create(['id' => $id, 'permissions' => $permissions]);
    }

    public function test_a_linked_key_with_the_permission_is_authorized_with_no_glue_code(): void
    {
        $project = Project::create([]);
        $key = $this->key('key-1', ['edit_projects']);
        $project->grantApiKey($key);

        AccessGuard::resolveApiKeyUsing(fn () => $key);

        $this->assertTrue(AccessGuard::allows('edit_projects', $project));
    }

    public function test_a_key_not_linked_to_the_entity_is_denied(): void
    {
        $project = Project::create([]);
        $other = Project::create([]);
        $key = $this->key('key-1', ['edit_projects']);
        $project->grantApiKey($key);

        AccessGuard::resolveApiKeyUsing(fn () => $key);

        $this->assertTrue(AccessGuard::denies('edit_projects', $other));
    }

    public function test_a_linked_key_without_the_permission_is_denied(): void
    {
        $project = Project::create([]);
        $key = $this->key('key-1', []);
        $project->grantApiKey($key);

        AccessGuard::resolveApiKeyUsing(fn () => $key);

        $this->assertTrue(AccessGuard::denies('edit_projects', $project));
    }

    public function test_revoking_a_key_removes_authorization(): void
    {
        $project = Project::create([]);
        $key = $this->key('key-1', ['edit_projects']);
        $project->grantApiKey($key);
        $project->revokeApiKey($key);

        AccessGuard::resolveApiKeyUsing(fn () => $key);

        $this->assertTrue(AccessGuard::denies('edit_projects', $project));
    }

    public function test_entity_exposes_its_linked_keys(): void
    {
        $project = Project::create([]);
        $key = $this->key('key-1', ['edit_projects']);
        $project->grantApiKey($key);

        $this->assertCount(1, $project->apiKeys()->get());
        $this->assertTrue($project->apiKeys()->first()->is($key));
    }
}
