<?php

namespace Langsys\AccessGuard\Contracts;

/**
 * Marker interface for entities that authorization is scoped to — the org,
 * project, team, workspace, tenant, etc. that a subject holds access within.
 */
interface GuardableResource
{
}
