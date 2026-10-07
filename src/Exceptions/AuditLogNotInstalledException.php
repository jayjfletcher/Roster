<?php

declare(strict_types=1);

namespace JayI\Roster\Exceptions;

use RuntimeException;

/**
 * Roster keeps no audit log of its own; recording needs jayi/keen.
 */
final class AuditLogNotInstalledException extends RuntimeException
{
    public static function make(): self
    {
        return new self('Roster no longer keeps its own audit log. Install jayi/keen (composer require jayi/keen, then php artisan migrate) and record with Keen::record().');
    }
}
