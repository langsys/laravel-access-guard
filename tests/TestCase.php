<?php

namespace Langsys\AccessGuard\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Langsys\AccessGuard\AccessGuardServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [AccessGuardServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('cache.default', 'array');
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Subject + entity tables used by the assignment / gate / middleware tests.
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_super')->default(false);
        });

        Schema::create('projects', function (Blueprint $table) {
            $table->id();
        });
    }
}
