<?php

namespace Langsys\AccessGuard\Contracts;

/**
 * Optional contract for API keys that act for someone, usually the person who
 * created the key. Implement it on the key subject: the AuthorizableByKey model
 * itself, or the key class adapted by the bridge.
 *
 * A key that acts for someone is authorized only when both halves pass:
 *
 *  - the key's own checks: it holds the permission and is linked to the entity;
 *  - the acting subject's own check for the same permission and entity, the
 *    one it would face making the request itself (AuthorizableInEntity or
 *    AuthorizableByUser, including userHasDisabledEntity()).
 *
 * So a key never does what its subject couldn't. An acting subject whose
 * isSuperAdmin() is true passes its own half; the key's own checks still apply.
 * The request-level super-admin shortcut does not apply to such a key: a super
 * admin resolved as the request's user does not lift the key's limits either.
 *
 * One key class can serve keys that act for someone and keys that don't:
 * actsForSomeone() decides per key.
 */
interface ActsOnBehalfOf
{
    /**
     * Whether this key acts for a subject. When false, the key is authorized by
     * its own checks alone, exactly as a key without this contract.
     */
    public function actsForSomeone(): bool;

    /**
     * The subject this key acts for. Read only when actsForSomeone() is true;
     * null then denies, so a key whose subject is gone fails closed.
     */
    public function actsOnBehalfOf(): ?object;
}
