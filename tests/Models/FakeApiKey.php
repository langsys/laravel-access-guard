<?php

namespace Langsys\AccessGuard\Tests\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Stands in for an external API-key model (e.g. langsys/laravel-api-keys' ApiKey)
 * in the bridge tests — it has hasPermission() and a key, nothing more.
 */
class FakeApiKey extends Model
{
    protected $table = 'fake_api_keys';

    protected $guarded = [];

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $casts = ['permissions' => 'array'];

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions ?? [], true);
    }
}
