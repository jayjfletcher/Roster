<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Exceptions;

use RuntimeException;

/**
 * Roster keeps no audit log of its own; recording needs refactor-circus/keen.
 */
final class AuditLogNotInstalledException extends RuntimeException
{
    public static function make(): self
    {
        return new self('Roster no longer keeps its own audit log. Install refactor-circus/keen (composer require refactor-circus/keen, then php artisan migrate) and record with Keen::record().');
    }
}
