<?php

namespace Langsys\AccessGuard\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Langsys\AccessGuard\Concerns\AuthorizesWithApiKeys;
use Langsys\AccessGuard\Contracts\GuardableResource;
use Langsys\AccessGuard\Contracts\HasGuardableParent;

/**
 * A project whose parent is its organization.
 */
class OrganizationProject extends Model implements GuardableResource, HasGuardableParent
{
    use AuthorizesWithApiKeys;

    protected $table = 'organization_projects';

    protected $guarded = [];

    public $timestamps = false;

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function guardableParent(): ?GuardableResource
    {
        return $this->organization;
    }
}
