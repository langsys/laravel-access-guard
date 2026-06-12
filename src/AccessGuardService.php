<?php

namespace Langsys\AccessGuard;

use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Langsys\AccessGuard\Contracts\Authorizable;
use Langsys\AccessGuard\Contracts\AuthorizableByKey;
use Langsys\AccessGuard\Contracts\AuthorizableByUser;
use Langsys\AccessGuard\Contracts\GuardableResource;

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
     * Authorize the current subject for a permission on an entity.
     *
     * @throws AuthorizationException when access is denied.
     */
    public function authorize(string $permission, ?GuardableResource $entity): void
    {
        $user = $this->resolveUser();

        if ($user instanceof Authorizable && $user->isSuperAdmin()) {
            return;
        }

        // An API key on the request takes precedence — it identifies a
        // machine-to-machine call rather than the session user (if any).
        $subject = $this->resolveApiKey() ?? $user;

        if (! $subject || ! $entity) {
            throw new AuthorizationException();
        }

        match (true) {
            $subject instanceof AuthorizableByKey => $this->authorizeKey($subject, $permission, $entity),
            $subject instanceof AuthorizableByUser => $this->authorizeUser($subject, $permission, $entity),
            default => throw new AuthorizationException(),
        };
    }

    public function allows(string $permission, ?GuardableResource $entity): bool
    {
        try {
            $this->authorize($permission, $entity);

            return true;
        } catch (AuthorizationException) {
            return false;
        }
    }

    public function denies(string $permission, ?GuardableResource $entity): bool
    {
        return ! $this->allows($permission, $entity);
    }

    /**
     * Return only the items the current subject is authorized to access.
     * Items that are not GuardableResources pass through untouched.
     */
    public function filterByPermission(string $permission, Collection $collection): Collection
    {
        return $collection->filter(function ($item) use ($permission) {
            if (! $item instanceof GuardableResource) {
                return true;
            }

            return $this->allows($permission, $item);
        });
    }

    private function authorizeKey(AuthorizableByKey $key, string $permission, GuardableResource $entity): void
    {
        if (! $key->keyHasPermission($permission) || ! $key->keyBelongsToEntity($entity)) {
            throw new AuthorizationException();
        }
    }

    private function authorizeUser(AuthorizableByUser $user, string $permission, GuardableResource $entity): void
    {
        $role = $user->userRoleInEntity($entity);

        if (! $role
            || ! $user->roleHasPermission($role, $permission)
            || $user->userHasDisabledEntity($entity)
        ) {
            throw new AuthorizationException();
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
            $key = ($this->apiKeyResolver)();

            return $key instanceof AuthorizableByKey ? $key : null;
        }

        $attribute = config('access-guard.api_key_request_attribute', 'api_key');
        $key = request()?->attributes->get($attribute);

        return $key instanceof AuthorizableByKey ? $key : null;
    }
}
