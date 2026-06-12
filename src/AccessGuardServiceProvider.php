<?php

namespace Langsys\AccessGuard;

use Illuminate\Support\ServiceProvider;

class AccessGuardServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/access-guard.php', 'access-guard');

        $this->app->singleton(AccessGuardService::class);
        $this->app->alias(AccessGuardService::class, 'access-guard');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/access-guard.php' => config_path('access-guard.php'),
            ], 'access-guard-config');

            $this->publishesMigrations([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], 'access-guard-migrations');
        }
    }
}
