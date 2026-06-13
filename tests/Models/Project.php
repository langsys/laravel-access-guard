<?php

namespace Langsys\AccessGuard\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Langsys\AccessGuard\Concerns\AuthorizesWithApiKeys;
use Langsys\AccessGuard\Contracts\GuardableResource;

class Project extends Model implements GuardableResource
{
    use AuthorizesWithApiKeys;

    protected $table = 'projects';

    protected $guarded = [];

    public $timestamps = false;
}
