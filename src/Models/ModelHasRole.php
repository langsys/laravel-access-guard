<?php

namespace Langsys\AccessGuard\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Langsys\AccessGuard\Support\Config;

/**
 * Pivot linking a subject (model_type/model_id) to a role within a specific
 * entity (entity_type/entity_id) — the entity-scoped role assignment.
 */
class ModelHasRole extends Model
{
    protected $guarded = [];

    public function getTable(): string
    {
        return Config::table('model_has_roles');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(config('access-guard.models.role', Role::class), 'role_id');
    }
}
