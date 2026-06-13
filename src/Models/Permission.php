<?php

namespace Langsys\AccessGuard\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Langsys\AccessGuard\Concerns\HasUuid;
use Langsys\AccessGuard\Concerns\RefreshesPermissionCache;
use Langsys\AccessGuard\Support\Config;

class Permission extends Model
{
    use HasUuid;
    use RefreshesPermissionCache;

    protected $fillable = ['value', 'label'];

    public function getTable(): string
    {
        return Config::table('permissions');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            config('access-guard.models.role', Role::class),
            Config::table('role_has_permissions'),
            'permission_id',
            'role_id',
        );
    }
}
