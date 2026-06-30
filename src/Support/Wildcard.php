<?php

namespace Langsys\AccessGuard\Support;

class Wildcard
{
    /**
     * Whether any held permission satisfies the requested one, treating `*` as a
     * wildcard segment. e.g. held `projects.*` satisfies `projects.edit`, and a
     * held `*` satisfies anything.
     *
     * @param array<int, string> $held
     */
    public static function matches(array $held, string $requested, string $separator = '.'): bool
    {
        if (in_array($requested, $held, true)) {
            return true;
        }

        $requestedParts = explode($separator, $requested);

        foreach ($held as $pattern) {
            if ($pattern === '*') {
                return true;
            }

            if (self::patternMatches(explode($separator, $pattern), $requestedParts)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<int, string> $pattern
     * @param array<int, string> $requested
     */
    private static function patternMatches(array $pattern, array $requested): bool
    {
        foreach ($pattern as $i => $segment) {
            if ($segment === '*') {
                return true; // matches this segment and everything after it
            }

            if (($requested[$i] ?? null) !== $segment) {
                return false;
            }
        }

        return count($pattern) === count($requested);
    }
}
