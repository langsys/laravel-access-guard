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

AccessGuard::authorize('edit_projects', $project); // throws UnauthorizedException (403) if denied

if (AccessGuard::allows('view_projects', $project)) { /* ... */ }

// Keep only the entities the current subject may see:
$visible = AccessGuard::filterByPermission('view_projects', $projects);
```

## Wiring your models

Implement the contracts on your own models — Access Guard never assumes your
schema.

> Don't have a role-assignment scheme yet? Use the `HasRolesInEntities` trait
> (see *Assigning roles* below) and skip writing `userRoleInEntity()` by hand.

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

## Using with laravel-api-keys (zero-config)

Install both packages and it just works — no subclassing, no contract to
implement. When a key from `langsys/laravel-api-keys` authenticates, its
middleware puts it on the request and Access Guard adapts it automatically: the
key's own permissions are checked, and it is authorized against an entity only if
it has been linked to that entity.

Link keys to entities with the `AuthorizesWithApiKeys` trait:

```php
use Langsys\AccessGuard\Concerns\AuthorizesWithApiKeys;
use Langsys\AccessGuard\Contracts\GuardableResource;

class Project extends Model implements GuardableResource
{
    use AuthorizesWithApiKeys;
}

$project->grantApiKey($apiKey);   // this key may now act on this project
$project->revokeApiKey($apiKey);
```

`AccessGuard::authorize('edit_projects', $project)` then passes for a key that
both holds `edit_projects` and is linked to `$project`, and is denied otherwise.
Linking is the one explicit step — it's a security boundary — and it lives in the
`entity_has_api_keys` pivot. Without api-keys installed, only the user path runs.

**Other key systems:** any object implementing `AuthorizableByKey` is used as-is.
Point `config('access-guard.api_key.bridge')` at a different key class to
auto-adapt it, or set it to `null` to turn the bridge off.

## Assigning roles (batteries included)

If you don't already have a membership scheme, add the `HasRolesInEntities` trait
to your subject and implement `AuthorizableInEntity`. You get entity-scoped
assignment backed by the `model_has_roles` pivot — no custom pivot or
`userRoleInEntity()` to write:

```php
use Langsys\AccessGuard\Concerns\HasRolesInEntities;
use Langsys\AccessGuard\Contracts\AuthorizableInEntity;

class User extends Authenticatable implements Authorizable, AuthorizableInEntity
{
    use HasRolesInEntities;
}
```

```php
$user->assignRole('project_admin', $project);   // role value, backed enum, or Role model
$user->syncRoles(['viewer'], $project);
$user->removeRole('project_admin', $project);

$user->hasRole('project_admin', $project);                // bool
$user->hasPermissionInEntity('edit_projects', $project);  // unions every role + direct grant
$user->rolesInEntity($project);                           // Collection<Role>
$user->permissionsInEntity($project);                     // array<string> (roles ∪ direct)
```

A subject can hold multiple roles in one entity; permission checks union them.
Override `entityIsDisabled($entity)` to exclude a subject from an entity even when
a role would grant access (e.g. a user who left a project).

> Already have your own pivot (like langsys's `organization_has_users.role`)? Skip
> the trait and implement `AuthorizableByUser` instead — both paths are supported.

### Direct permissions

Sometimes a subject needs one permission in an entity without minting a role for
it. Grant it directly — backed by the `model_has_permissions` pivot, still
entity-scoped:

```php
$user->givePermission('export_data', $project);
$user->revokePermission('export_data', $project);

$user->directPermissionsInEntity($project);   // array<string> — just the direct grants
$user->permissionsInEntity($project);         // array<string> — roles ∪ direct grants
```

`hasPermissionInEntity()` and every Gate check see the **union** of role-derived
and direct permissions, so the two compose freely.

## Wildcard permissions

Off by default (checks are exact-match). Enable `wildcard.enabled` and a held
permission may use `*` as a segment wildcard:

```php
// config/access-guard.php → 'wildcard' => ['enabled' => true, 'separator' => '.']

$role->grantPermissions(['projects.*']);
$user->hasPermissionInEntity('projects.edit', $project);   // true
$user->hasPermissionInEntity('projects.delete', $project); // true
$user->hasPermissionInEntity('users.edit', $project);      // false

$role->grantPermissions(['*']);                            // grants everything
```

Wildcards apply only to **held** permissions; the permission you check for is
always literal. With wildcards off, `projects.*` is just a permission named
`projects.*`.

## Gate & policy integration

With `register_gate` on (default), Gate checks against a `GuardableResource` route
through Access Guard, so the idiomatic Laravel APIs just work:

```php
$user->can('edit_projects', $project);
$this->authorize('edit_projects', $project);   // in a controller
// @can('edit_projects', $project) ... @endcan
```

A subject whose `isSuperAdmin()` is true passes every Gate check
(`super_admin_via_gate`). Checks that aren't against a `GuardableResource` are left
untouched for your own gates and policies.

Permissions already work in Blade through `@can` (the Gate integration above). For
the **entity-scoped role** check there's a dedicated directive:

```blade
@hasrole('project_admin', $project)
    {{-- visible only to admins of this project --}}
@endhasrole
```

## Route middleware

```php
Route::get('/projects/{project}', [ProjectController::class, 'show'])
    ->middleware('access-guard:view_projects,project');
```

The first argument is the permission; the optional second names the route
parameter holding the entity (otherwise the first route-bound `GuardableResource`
is used). Denial throws `UnauthorizedException` (403).

## Exceptions

A denied `authorize()` throws `Langsys\AccessGuard\Exceptions\UnauthorizedException`,
which extends Laravel's `AuthorizationException` (so it still renders as a 403 and
existing handlers keep working). It carries the context that was denied:

```php
use Langsys\AccessGuard\Exceptions\UnauthorizedException;

try {
    AccessGuard::authorize('edit_projects', $project);
} catch (UnauthorizedException $e) {
    $e->permission; // 'edit_projects'
    $e->entity;     // $project
}
```

The permission name is **omitted from the exception message by default** so you
don't leak your permission vocabulary in production responses; set
`display_permission_in_exception => true` to include it (handy in local dev).
Looking up a role that doesn't exist throws `RoleDoesNotExist`.

## Caching

The role → permission map is cached (config `cache.store` / `expiration_time`) and
flushed automatically on grant/revoke and any role/permission save or delete. Flush
manually with `php artisan access-guard:cache-reset`.

## Artisan commands

```bash
php artisan access-guard:create-permission view_projects "View projects"
php artisan access-guard:create-role project_admin "Project Admin" --permissions=view_projects,edit_projects
php artisan access-guard:show          # roles and the permissions they grant
php artisan access-guard:cache-reset
```

## Enums

Anywhere a permission or role name is accepted you can pass a string **or** a
backed enum:

```php
enum Ability: string { case ViewProjects = 'view_projects'; }

$role->grantPermissions([Ability::ViewProjects]);
AccessGuard::allows(Ability::ViewProjects, $project);
```

## Events

Set `events_enabled => true` to fire `RoleAssignedToModel`, `RoleRemovedFromModel`,
`PermissionAssignedToRole`, and `PermissionRemovedFromRole` — useful for audit logging.

## Testing

```bash
composer install
composer test
```
