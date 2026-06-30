<?php

namespace Langsys\AccessGuard\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Langsys\AccessGuard\Support\Config;

/**
 * Pivot for a permission granted directly to a subject (model_type/model_id)
 * within an entity (entity_type/entity_id) — a one-off grant outside any role.
 */
class ModelHasPermission extends Model
{
    protected $guarded = [];

    public function getTable(): string
    {
        return Config::table('model_has_permissions');
    }

    public function permission(): BelongsTo
    {
        return $this->belongsTo(config('access-guard.models.permission', Permission::class), 'permission_id');
    }
}
