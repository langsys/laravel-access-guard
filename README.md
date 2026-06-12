# Laravel Access Guard

Entity-scoped role-based authorization for Laravel. Where
[`spatie/laravel-permission`](https://github.com/spatie/laravel-permission)
gives users **global** roles, Access Guard scopes a role to an **entity** — a
user is an *admin of this organization* and a *viewer of that project* — and
treats **API keys as first-class authorizable subjects** alongside users.

It authorizes against **interfaces you implement on your own models**, so it
works for any hierarchy (orgs, projects, teams, workspaces, tenants). No hard
dependency on any other package; pairs naturally with
[`langsys/laravel-api-keys`](https://github.com/langsys/laravel-api-keys).

## Installation

```bash
composer require langsys/laravel-access-guard
php artisan vendor:publish --tag=access-guard-migrations
php artisan vendor:publish --tag=access-guard-config   # optional
php artisan migrate
```

Migrations are guarded with `Schema::hasTable()`, so publishing into an app that
already has `roles` / `permissions` tables is a safe no-op.

## The model

Authorization answers one question: **may this subject perform this permission
on this entity?** A subject is either a user (with a role *in* the entity) or an
API key (linked *to* the entity). Permissions are plain strings.

```php
use Langsys\AccessGuard\Facades\AccessGuard;

AccessGuard::authorize('edit_projects', $project); // throws AuthorizationException if denied

if (AccessGuard::allows('view_projects', $project)) { /* ... */ }

// Keep only the entities the current subject may see:
$visible = AccessGuard::filterByPermission('view_projects', $projects);
```

## Wiring your models

Implement the contracts on your own models — Access Guard never assumes your
schema.

**User** (`AuthorizableByUser`, plus optional `Authorizable` for super admins):

```php
use Langsys\AccessGuard\Contracts\Authorizable;
use Langsys\AccessGuard\Contracts\AuthorizableByUser;

class User extends Authenticatable implements Authorizable, AuthorizableByUser
{
    public function isSuperAdmin(): bool
    {
        return $this->type === UserType::SuperAdmin;
    }

    public function userRoleInEntity(mixed $entity): ?object
    {
        return $this->roleInEntity($entity); // your pivot lookup → a Role
    }

    public function roleHasPermission(object $role, string $permission): bool
    {
        return $role->hasPermission($permission);
    }

    public function userHasDisabledEntity(mixed $entity): bool
    {
        return $this->disabledEntities()->whereKey($entity->getKey())->exists();
    }
}
```

**Entity** — mark anything you authorize against with `GuardableResource`:

```php
use Langsys\AccessGuard\Contracts\GuardableResource;

class Project extends Model implements GuardableResource {}
```

**API key** (optional, `AuthorizableByKey`) — see the api-keys integration below.

## Roles & permissions

The bundled `Role` and `Permission` models (UUID keys, `value` + `label`) cover
the vocabulary; `role_has_permissions` links them.

```php
use Langsys\AccessGuard\Models\Role;

$admin = Role::create(['value' => 'project_admin', 'label' => 'Project Admin']);
$admin->grantPermissions(['view_projects', 'edit_projects']); // creates missing permissions
$admin->hasPermission('view_projects'); // true
```

Seed these from a seeder; override the models via `config('access-guard.models')`.

## How the subject is resolved

On each `authorize()` call, Access Guard:

1. checks for a super admin (the resolved user implements `Authorizable` and
   `isSuperAdmin()` is true) → allow;
2. otherwise picks the **API key** if one is present on the request, else the
   **authenticated user**;
3. runs the key path (`keyHasPermission` **and** `keyBelongsToEntity`) or the
   user path (`userRoleInEntity` → `roleHasPermission`, and not
   `userHasDisabledEntity`).

By default the user comes from `Auth::user()` and the API key from the
`api_key` request attribute (set by `langsys/laravel-api-keys`). Override either
from a service provider:

```php
AccessGuard::resolveUserUsing(fn () => /* ... */);
AccessGuard::resolveApiKeyUsing(fn () => /* ... */);
```

## Using with laravel-api-keys

Make your API key model satisfy `AuthorizableByKey` — the two packages meet at
permission strings, neither depends on the other:

```php
use Langsys\AccessGuard\Contracts\AuthorizableByKey;

class ApiKey extends \Langsys\ApiKeys\Models\ApiKey implements AuthorizableByKey
{
    public function keyHasPermission(string $permission): bool
    {
        return $this->hasPermission($permission);
    }

    public function keyBelongsToEntity(mixed $entity): bool
    {
        return $entity->apiKeys()->whereKey($this->getKey())->exists();
    }
}
```

The api-keys middleware drops the authenticated key onto the request, where
Access Guard's default resolver finds it. Without api-keys installed, only the
user path runs.

## Testing

```bash
composer install
composer test
```
