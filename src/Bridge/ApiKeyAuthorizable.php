<?php

namespace Langsys\AccessGuard\Bridge;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Langsys\AccessGuard\Contracts\AuthorizableByKey;
use Langsys\AccessGuard\Contracts\GuardableResource;
use Langsys\AccessGuard\Support\Config;
use Langsys\AccessGuard\Support\Wildcard;

/**
 * Adapts a third-party API key (e.g. langsys/laravel-api-keys' ApiKey) to the
 * AuthorizableByKey contract with zero consumer code: permission checks delegate
 * to the key's own hasPermission(), and entity linkage is read from the
 * entity_has_api_keys pivot (written via the AuthorizesWithApiKeys trait).
 */
class ApiKeyAuthorizable implements AuthorizableByKey
{
    public function __construct(private object $key)
    {
    }

    public function keyHasPermission(string $permission): bool
    {
        if (config('access-guard.wildcard.enabled', false) && method_exists($this->key, 'permissionValues')) {
            return Wildcard::matches(
                $this->key->permissionValues(),
                $permission,
                config('access-guard.wildcard.separator', '.'),
            );
        }

        return $this->key->hasPermission($permission);
    }

    public function keyBelongsToEntity(mixed $entity): bool
    {
        if (! $entity instanceof GuardableResource || ! $entity instanceof Model) {
            return false;
        }

        return DB::table(Config::table('entity_has_api_keys'))
            ->where('entity_type', $entity->getMorphClass())
            ->where('entity_id', (string) $entity->getKey())
            ->where('api_key_id', (string) $this->key->getKey())
            ->exists();
    }

    public function key(): object
    {
        return $this->key;
    }
}
