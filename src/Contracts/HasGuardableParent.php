<?php

namespace Langsys\AccessGuard\Contracts;

/**
 * Optional contract for entities that sit inside another entity — a project in
 * an organization, a team in a workspace. An API key linked to an entity then
 * reaches everything below it: the bridge's keyBelongsToEntity() passes when
 * the key is linked to the entity itself or to any ancestor reached through
 * guardableParent(). Reach runs downward only; a link to a child never reaches
 * its parent.
 *
 * Only API-key links read the parent. A user's roles stay scoped to the entity
 * they were granted in.
 */
interface HasGuardableParent
{
    /**
     * The entity directly above this one, or null at the top.
     */
    public function guardableParent(): ?GuardableResource;
}
