<?php

namespace Langsys\AccessGuard\Exceptions;

use InvalidArgumentException;

class RoleDoesNotExist extends InvalidArgumentException
{
    public static function named(string $value): self
    {
        return new self("There is no role with value `{$value}`.");
    }
}
