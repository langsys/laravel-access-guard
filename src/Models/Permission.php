<?php

namespace Langsys\AccessGuard\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Langsys\AccessGuard\Concerns\HasUuid;

class Permission extends Model
{
    use HasUuid;

    protected $table = 'permissions';

    protected $fillable = ['value', 'label'];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            config('access-guard.models.role', Role::class),
            'role_has_permissions',
            'permission_id',
            'role_id',
        );
    }
}
