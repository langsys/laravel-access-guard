<?php

namespace Langsys\AccessGuard\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Langsys\AccessGuard\Contracts\GuardableResource;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route middleware: `access-guard:edit_projects,project`.
 *
 * The first argument is the permission. The optional second argument names the
 * route parameter holding the entity; if omitted, the first route-bound
 * GuardableResource is used. Throws AuthorizationException (403) on denial.
 */
class EnsurePermission
{
    public function handle(Request $request, Closure $next, string $permission, ?string $entityParam = null): Response
    {
        app('access-guard')->authorize($permission, $this->resolveEntity($request, $entityParam));

        return $next($request);
    }

    private function resolveEntity(Request $request, ?string $entityParam): ?GuardableResource
    {
        if ($entityParam !== null) {
            $value = $request->route($entityParam);

            return $value instanceof GuardableResource ? $value : null;
        }

        foreach ((array) $request->route()?->parameters() as $parameter) {
            if ($parameter instanceof GuardableResource) {
                return $parameter;
            }
        }

        return null;
    }
}
