<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Invitation\Exceptions;

use RuntimeException;

final class InvalidInvitationException extends RuntimeException
{
    public static function forToken(): self
    {
        return new self('The invitation link is invalid.');
    }
}
