<?php

declare(strict_types=1);

namespace JayI\Roster\Transfers;

use RuntimeException;

final class TransfersUnavailableException extends RuntimeException
{
    public static function make(): self
    {
        return new self('CSV import and export need jayi/impex: composer require jayi/impex, then publish and run its migrations (php artisan vendor:publish --tag=impex-migrations && php artisan migrate) and run a queue worker.');
    }
}
