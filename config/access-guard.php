<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Role & Permission Models
    |--------------------------------------------------------------------------
    |
    | The Eloquent models backing the RBAC vocabulary. Override to extend them
    | (e.g. add relations or scopes) and point these at your subclasses.
    |
    */
    'models' => [
        'role' => Langsys\AccessGuard\Models\Role::class,
        'permission' => Langsys\AccessGuard\Models\Permission::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Table Names
    |--------------------------------------------------------------------------
    |
    | If langsys/laravel-api-keys is installed, it stores its key permissions in
    | the SAME `permissions` table (its migration skips creation when the table
    | exists). Its `api-keys.tables.permissions` key must agree with the value
    | below — setting one without the other silently produces two separate
    | permission tables.
    |
    */
    'tables' => [
        'roles' => 'roles',
        'permissions' => 'permissions',
        'role_has_permissions' => 'role_has_permissions',
        'model_has_roles' => 'model_has_roles',
        'model_has_permissions' => 'model_has_permissions',
        'entity_has_api_keys' => 'entity_has_api_keys',
    ],

    /*
    |--------------------------------------------------------------------------
    | API Key Request Attribute
    |--------------------------------------------------------------------------
    |
    | When resolving the subject of the current request, the default API-key
    | resolver reads this request attribute. The langsys/laravel-api-keys
    | middleware populates `api_key` out of the box. Only a value implementing
    | AuthorizableByKey is treated as an API-key subject.
    |
    | To resolve users or API keys differently, call AccessGuard::resolveUserUsing()
    | / AccessGuard::resolveApiKeyUsing() from a service provider instead.
    |
    */
    'api_key_request_attribute' => 'api_key',

    /*
    |--------------------------------------------------------------------------
    | API Key Bridge
    |--------------------------------------------------------------------------
    |
    | When an API key of this class lands on the request (e.g. from
    | langsys/laravel-api-keys, whose middleware sets the `api_key` attribute)
    | and it does not itself implement AuthorizableByKey, it is adapted
    | automatically — its permissions are checked via hasPermission() and its
    | entity linkage via the entity_has_api_keys pivot. Set to null to disable.
    |
    | Keys that already implement AuthorizableByKey are always used as-is, so any
    | other API-key system can be plugged in directly.
    |
    */
    'api_key' => [
        'bridge' => Langsys\ApiKeys\Models\ApiKey::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Gate Integration
    |--------------------------------------------------------------------------
    |
    | When enabled, Gate checks against a GuardableResource entity are routed
    | through Access Guard — so $user->can('edit_projects', $project), @can, and
    | controller authorize() calls all work. With super_admin_via_gate, a subject
    | whose isSuperAdmin() is true passes every Gate check.
    |
    */
    'register_gate' => true,
    'super_admin_via_gate' => true,

    /*
    |--------------------------------------------------------------------------
    | Events
    |--------------------------------------------------------------------------
    |
    | When enabled, role assignment and permission grant/revoke fire events
    | (RoleAssignedToModel, RoleRemovedFromModel, PermissionAssignedToRole,
    | PermissionRemovedFromRole). Off by default to avoid overhead.
    |
    */
    'events_enabled' => false,

    /*
    |--------------------------------------------------------------------------
    | Wildcard Permissions
    |--------------------------------------------------------------------------
    |
    | When enabled, a held permission may use `*` as a wildcard segment — e.g.
    | granting `projects.*` satisfies a check for `projects.edit`, and `*` grants
    | everything. Off by default; checks are exact-match.
    |
    */
    'wildcard' => [
        'enabled' => false,
        'separator' => '.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Exceptions
    |--------------------------------------------------------------------------
    |
    | Include the missing permission name in the UnauthorizedException message.
    | Leave false in production to avoid leaking your permission vocabulary.
    |
    */
    'display_permission_in_exception' => false,

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | The role → permission map is cached to avoid re-querying on every check.
    | It is flushed automatically on grant/revoke and any role/permission save
    | or delete; flush manually with `php artisan access-guard:cache-reset`.
    |
    */
    'cache' => [
        'store' => 'default',
        'key' => 'access-guard.permissions',
        'expiration_time' => 60 * 60 * 24,
    ],

];
