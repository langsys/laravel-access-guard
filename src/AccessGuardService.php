<?php

namespace Langsys\AccessGuard;

use BackedEnum;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Langsys\AccessGuard\Contracts\Authorizable;
use Langsys\AccessGuard\Contracts\AuthorizableByKey;
use Langsys\AccessGuard\Contracts\AuthorizableByUser;
use Langsys\AccessGuard\Contracts\AuthorizableInEntity;
use Langsys\AccessGuard\Contracts\GuardableResource;
use Langsys\AccessGuard\Bridge\ApiKeyAuthorizable;
use Langsys\AccessGuard\Exceptions\UnauthorizedException;
use Langsys\AccessGuard\Support\Config;

class AccessGuardService
{
    private ?Closure $userResolver = null;

    private ?Closure $apiKeyResolver = null;

    /**
     * Override how the current user subject is resolved (defaults to Auth::user()).
     */
    public function resolveUserUsing(Closure $resolver): static
    {
        $this->userResolver = $resolver;

        return $this;
    }

    /**
     * Override how the current API-key subject is resolved (defaults to the
     * `api_key` request attribute set by langsys/laravel-api-keys).
     */
    public function resolveApiKeyUsing(Closure $resolver): static
    {
        $this->apiKeyResolver = $resolver;

        return $this;
    }

    /**
     * Authorize the current request's subject for a permission on an entity.
     *
     * @throws AuthorizationException when access is denied.
     */
    public function authorize(BackedEnum|string $permission, ?GuardableResource $entity): void
    {
        $user = $this->resolveUser();
        $subject = $this->resolveApiKey() ?? $user;

        $this->enforce($user, $subject, Config::value($permission), $entity);
    }

    public function allows(BackedEnum|string $permission, ?GuardableResource $entity): bool
    {
        try {
            $this->authorize($permission, $entity);

            return true;
        } catch (AuthorizationException) {
            return false;
        }
    }

    public function denies(BackedEnum|string $permission, ?GuardableResource $entity): bool
    {
        return ! $this->allows($permission, $entity);
    }

    /**
     * Authorize an explicit subject (used by the Gate integration, where the
     * subject is the Gate user rather than the request's resolved subject).
     */
    public function allowsForUser(?object $user, BackedEnum|string $permission, ?GuardableResource $entity): bool
    {
        try {
            $this->enforce($user, $user, Config::value($permission), $entity);

            return true;
        } catch (AuthorizationException) {
            return false;
        }
    }

    /**
     * Return only the items the current subject is authorized to access.
     * Items that are not GuardableResources pass through untouched.
     */
    public function filterByPermission(BackedEnum|string $permission, Collection $collection): Collection
    {
        return $collection->filter(function ($item) use ($permission) {
            if (! $item instanceof GuardableResource) {
                return true;
            }

            return $this->allows($permission, $item);
        });
    }

    private function enforce(?object $user, ?object $subject, string $value, ?GuardableResource $entity): void
    {
        if ($user instanceof Authorizable && $user->isSuperAdmin()) {
            return;
        }

        if (! $subject || ! $entity) {
            throw UnauthorizedException::forPermission($value, $entity);
        }

        match (true) {
            $subject instanceof AuthorizableByKey => $this->authorizeKey($subject, $value, $entity),
            $subject instanceof AuthorizableInEntity => $this->authorizeInEntity($subject, $value, $entity),
            $subject instanceof AuthorizableByUser => $this->authorizeUser($subject, $value, $entity),
            default => throw UnauthorizedException::forPermission($value, $entity),
        };
    }

    private function authorizeKey(AuthorizableByKey $key, string $value, GuardableResource $entity): void
    {
        if (! $key->keyHasPermission($value) || ! $key->keyBelongsToEntity($entity)) {
            throw UnauthorizedException::forPermission($value, $entity);
        }
    }

    private function authorizeInEntity(AuthorizableInEntity $user, string $value, GuardableResource $entity): void
    {
        if (! $user->hasPermissionInEntity($value, $entity)) {
            throw UnauthorizedException::forPermission($value, $entity);
        }
    }

    private function authorizeUser(AuthorizableByUser $user, string $value, GuardableResource $entity): void
    {
        $role = $user->userRoleInEntity($entity);

        if (! $role
            || ! $user->roleHasPermission($role, $value)
            || $user->userHasDisabledEntity($entity)
        ) {
            throw UnauthorizedException::forPermission($value, $entity);
        }
    }

    private function resolveUser(): mixed
    {
        if ($this->userResolver) {
            return ($this->userResolver)();
        }

        return Auth::user();
    }

    private function resolveApiKey(): ?AuthorizableByKey
    {
        if ($this->apiKeyResolver) {
            return $this->adaptApiKey(($this->apiKeyResolver)());
        }

        $attribute = config('access-guard.api_key_request_attribute', 'api_key');

        return $this->adaptApiKey(request()?->attributes->get($attribute));
    }

    /**
     * A key that already implements AuthorizableByKey is used directly (any
     * key system can plug in). Otherwise, a key of the configured bridge class
     * is adapted automatically — zero-config integration with laravel-api-keys.
     */
    private function adaptApiKey(mixed $key): ?AuthorizableByKey
    {
        if ($key instanceof AuthorizableByKey) {
            return $key;
        }

        if ($key === null) {
            return null;
        }

        $bridge = config('access-guard.api_key.bridge');

        if ($bridge && $key instanceof $bridge) {
            return new ApiKeyAuthorizable($key);
        }

        return null;
    }
}
