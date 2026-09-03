<?php

namespace Langsys\AccessGuard;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Langsys\AccessGuard\Commands\CacheResetCommand;
use Langsys\AccessGuard\Commands\CreatePermissionCommand;
use Langsys\AccessGuard\Commands\CreateRoleCommand;
use Langsys\AccessGuard\Commands\ShowCommand;
use Langsys\AccessGuard\Contracts\Authorizable;
use Langsys\AccessGuard\Contracts\GuardableResource;
use Langsys\AccessGuard\Http\Middleware\EnsurePermission;

class AccessGuardServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/access-guard.php', 'access-guard');

        $this->app->singleton(PermissionRegistrar::class);
        $this->app->singleton(AccessGuardService::class);
        $this->app->alias(AccessGuardService::class, 'access-guard');
    }

    public function boot(): void
    {
        $this->registerGate();
        $this->registerBladeDirectives();

        $this->app->make(Router::class)->aliasMiddleware('access-guard', EnsurePermission::class);

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/access-guard.php' => config_path('access-guard.php'),
            ], 'access-guard-config');

            // publishesMigrations() only exists on Laravel 11+; plain publishes()
            // covers Laravel 10 (illuminate/support ^10 is supported).
            $migrations = [__DIR__ . '/../database/migrations' => database_path('migrations')];

            if (method_exists($this, 'publishesMigrations')) {
                $this->publishesMigrations($migrations, 'access-guard-migrations');
            } else {
                $this->publishes($migrations, 'access-guard-migrations');
            }

            $this->commands([
                CreateRoleCommand::class,
                CreatePermissionCommand::class,
                ShowCommand::class,
                CacheResetCommand::class,
            ]);
        }
    }

    /**
     * Route Gate checks for GuardableResource entities through Access Guard, so
     * $user->can('edit_projects', $project), @can, and authorize() all work.
     *
     * The hook is grant-only: it returns true when the subject holds the
     * permission and abstains (null) otherwise — it never denies, so gates and
     * policies on the same models keep their full authority and an unpermitted
     * ability nothing else answers falls to the Gate's default deny.
     */
    private function registerGate(): void
    {
        if (! config('access-guard.register_gate', true)) {
            return;
        }

        Gate::before(function ($user, string $ability, array $arguments = []) {
            if (config('access-guard.super_admin_via_gate', true)
                && $user instanceof Authorizable
                && $user->isSuperAdmin()
            ) {
                return true;
            }

            $entity = $arguments[0] ?? null;

            if ($entity instanceof GuardableResource) {
                return app('access-guard')->allowsForUser($user, $ability, $entity) ?: null;
            }

            return null;
        });
    }

    /**
     * Permission checks already work in Blade via @can (the Gate integration).
     * This adds @hasrole($role, $entity) for the entity-scoped role check.
     */
    private function registerBladeDirectives(): void
    {
        if (! $this->app->bound('blade.compiler')) {
            return;
        }

        Blade::if('hasrole', function ($role, $entity) {
            $user = auth()->user();

            return $user !== null && method_exists($user, 'hasRole') && $user->hasRole($role, $entity);
        });
    }
}
