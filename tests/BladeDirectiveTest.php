<?php

namespace Langsys\AccessGuard\Tests;

use Illuminate\Support\Facades\Blade;
use Langsys\AccessGuard\Models\Role;
use Langsys\AccessGuard\Tests\Models\Project;
use Langsys\AccessGuard\Tests\Models\User;

class BladeDirectiveTest extends TestCase
{
    public function test_hasrole_directive_reflects_entity_scoped_roles(): void
    {
        $user = User::create([]);
        $project = Project::create([]);
        Role::create(['value' => 'admin', 'label' => 'Admin']);
        $user->assignRole('admin', $project);
        $this->actingAs($user);

        $granted = Blade::render('@hasrole("admin", $p) YES @endhasrole', ['p' => $project]);
        $this->assertStringContainsString('YES', $granted);

        $missing = Blade::render('@hasrole("editor", $p) YES @endhasrole', ['p' => $project]);
        $this->assertStringNotContainsString('YES', $missing);
    }
}
