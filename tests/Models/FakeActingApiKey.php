<?php

namespace Langsys\AccessGuard\Tests\Models;

use Langsys\AccessGuard\Contracts\ActsOnBehalfOf;

/**
 * A bridged key class (it does not implement AuthorizableByKey) that acts for
 * whoever the test sets on it.
 */
class FakeActingApiKey extends FakeApiKey implements ActsOnBehalfOf
{
    public bool $acting = false;

    public ?object $subject = null;

    public function actsFor(?object $subject): static
    {
        $this->acting = true;
        $this->subject = $subject;

        return $this;
    }

    public function actsForSomeone(): bool
    {
        return $this->acting;
    }

    public function actsOnBehalfOf(): ?object
    {
        return $this->subject;
    }
}
