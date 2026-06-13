<?php

namespace Langsys\AccessGuard\Tests\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Langsys\AccessGuard\Concerns\HasRolesInEntities;
use Langsys\AccessGuard\Contracts\Authorizable;
use Langsys\AccessGuard\Contracts\AuthorizableInEntity;

class User extends Authenticatable implements Authorizable, AuthorizableInEntity
{
    use HasRolesInEntities;

    protected $table = 'users';

    protected $guarded = [];

    public $timestamps = false;

    public function isSuperAdmin(): bool
    {
        return (bool) $this->getAttribute('is_super');
    }
}
