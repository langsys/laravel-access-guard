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
    */
    'tables' => [
        'roles' => 'roles',
        'permissions' => 'permissions',
        'role_has_permissions' => 'role_has_permissions',
        'model_has_roles' => 'model_has_roles',
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
