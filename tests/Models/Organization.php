<?php

namespace Langsys\AccessGuard\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Langsys\AccessGuard\Concerns\AuthorizesWithApiKeys;
use Langsys\AccessGuard\Contracts\GuardableResource;
use Langsys\AccessGuard\Contracts\HasGuardableParent;

/**
 * An organization that may sit inside a parent organization, so the walk can be
 * tested over more than one level and over a parent cycle.
 */
class Organization extends Model implements GuardableResource, HasGuardableParent
{
    use AuthorizesWithApiKeys;

    protected $table = 'organizations';

    protected $guarded = [];

    public $timestamps = false;

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function guardableParent(): ?GuardableResource
    {
        return $this->parent;
    }
}
