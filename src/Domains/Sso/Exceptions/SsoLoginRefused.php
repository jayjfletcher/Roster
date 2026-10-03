<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Sso\Exceptions;

use RuntimeException;

/**
 * Why an identity provider's sign-in was refused. Internal to
 * SsoLoginAction, which turns it into a validation error after recording it.
 */
final class SsoLoginRefused extends RuntimeException
{
    public function __construct(public readonly string $reason)
    {
        parent::__construct($reason);
    }
}
