<?php

namespace Langsys\AccessGuard\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Langsys\AccessGuard\Support\Config;

/**
 * Add to a GuardableResource entity to manage which API keys may act on it.
 * Backed by the entity_has_api_keys pivot — the same pivot the access-guard
 * bridge reads when authorizing an API key, so linking here is all it takes for
 * the key to be authorized against this entity.
 */
trait AuthorizesWithApiKeys
{
    public function apiKeys(): MorphToMany
    {
        return $this->morphToMany(
            config('access-guard.api_key.bridge'),
            'entity',
            Config::table('entity_has_api_keys'),
            'entity_id',
            'api_key_id',
        )->withTimestamps();
    }

    public function grantApiKey(mixed $key): static
    {
        $this->apiKeys()->syncWithoutDetaching([$this->apiKeyId($key)]);

        return $this;
    }

    public function revokeApiKey(mixed $key): static
    {
        $this->apiKeys()->detach([$this->apiKeyId($key)]);

        return $this;
    }

    private function apiKeyId(mixed $key): mixed
    {
        return is_object($key) ? $key->getKey() : $key;
    }
}
