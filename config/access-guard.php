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

];
