<?php

namespace Langsys\AccessGuard;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Langsys\AccessGuard\Support\Config;

/**
 * Caches the role → permission-values map so authorization checks don't re-query
 * the permission tables on every call. The map is small and changes rarely;
 * grant/revoke and any role/permission save or delete flush it.
 */
class PermissionRegistrar
{
    /** @var array<string, array<int, string>>|null */
    private ?array $rolePermissions = null;

    public function roleHasPermission(string $roleId, string $value): bool
    {
        return in_array($value, $this->rolePermissionMap()[$roleId] ?? [], true);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rolePermissionMap(): array
    {
        if ($this->rolePermissions !== null) {
            return $this->rolePermissions;
        }

        return $this->rolePermissions = $this->cache()->remember(
            config('access-guard.cache.key', 'access-guard.permissions'),
            (int) config('access-guard.cache.expiration_time', 86400),
            fn () => $this->loadMap(),
        );
    }

    public function forgetCachedPermissions(): void
    {
        $this->rolePermissions = null;
        $this->cache()->forget(config('access-guard.cache.key', 'access-guard.permissions'));
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function loadMap(): array
    {
        $rows = DB::table(Config::table('role_has_permissions') . ' as rhp')
            ->join(Config::table('permissions') . ' as p', 'p.id', '=', 'rhp.permission_id')
            ->get(['rhp.role_id', 'p.value']);

        $map = [];

        foreach ($rows as $row) {
            $map[$row->role_id][] = $row->value;
        }

        return $map;
    }

    private function cache(): CacheRepository
    {
        $store = config('access-guard.cache.store', 'default');

        return $store === 'default' ? Cache::store() : Cache::store($store);
    }
}
