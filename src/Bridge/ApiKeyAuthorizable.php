<?php

namespace Langsys\AccessGuard\Bridge;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Langsys\AccessGuard\Contracts\ActsOnBehalfOf;
use Langsys\AccessGuard\Contracts\AuthorizableByKey;
use Langsys\AccessGuard\Contracts\GuardableResource;
use Langsys\AccessGuard\Contracts\HasGuardableParent;
use Langsys\AccessGuard\Support\Config;
use Langsys\AccessGuard\Support\Wildcard;

/**
 * Adapts a third-party API key (e.g. langsys/laravel-api-keys' ApiKey) to the
 * AuthorizableByKey contract with zero consumer code: permission checks delegate
 * to the key's own hasPermission(), and entity linkage is read from the
 * entity_has_api_keys pivot (written via the AuthorizesWithApiKeys trait).
 *
 * A key class that implements ActsOnBehalfOf keeps that behaviour through the
 * bridge. A key model that implements AuthorizableByKey itself can still reuse
 * the pivot lookup: `(new ApiKeyAuthorizable($key))->keyBelongsToEntity($entity)`
 * needs nothing from the key but getKey().
 */
class ApiKeyAuthorizable implements AuthorizableByKey, ActsOnBehalfOf
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

    /**
     * Whether the key is linked to the entity or to any entity above it
     * (HasGuardableParent), answered in one query.
     */
    public function keyBelongsToEntity(mixed $entity): bool
    {
        if (! $entity instanceof GuardableResource) {
            return false;
        }

        $links = $this->linkableLineage($entity);

        if ($links === []) {
            return false;
        }

        return DB::table(Config::table('entity_has_api_keys'))
            ->where('api_key_id', (string) $this->key->getKey())
            ->where(function ($query) use ($links) {
                foreach ($links as [$type, $id]) {
                    $query->orWhere(fn ($pair) => $pair->where('entity_type', $type)->where('entity_id', $id));
                }
            })
            ->exists();
    }

    public function actsForSomeone(): bool
    {
        return $this->key instanceof ActsOnBehalfOf && $this->key->actsForSomeone();
    }

    public function actsOnBehalfOf(): ?object
    {
        return $this->key instanceof ActsOnBehalfOf ? $this->key->actsOnBehalfOf() : null;
    }

    public function key(): object
    {
        return $this->key;
    }

    /**
     * The (morph class, key) pair of the entity and of each ancestor that can
     * appear in the pivot, i.e. each Eloquent model. An ancestor that isn't a
     * model is walked through but contributes no pair. The walk stops at the
     * first entity it has already visited, so a parent cycle ends it.
     *
     * @return array<int, array{0: string, 1: string}>
     */
    private function linkableLineage(GuardableResource $entity): array
    {
        $links = [];
        $visited = [];

        for ($node = $entity; $node !== null; $node = $node instanceof HasGuardableParent ? $node->guardableParent() : null) {
            $identity = $node instanceof Model
                ? $node->getMorphClass() . '|' . $node->getKey()
                : 'object#' . spl_object_id($node);

            if (isset($visited[$identity])) {
                break;
            }

            // Holding the node keeps its object id from being reused by a
            // later parent built on the fly.
            $visited[$identity] = $node;

            if ($node instanceof Model && $node->getKey() !== null) {
                $links[] = [$node->getMorphClass(), (string) $node->getKey()];
            }
        }

        return $links;
    }
}
