<?php

declare(strict_types=1);

namespace JayI\Roster\Tests;

use JayI\Keen\KeenServiceProvider;

/**
 * Roster booted with jayi/keen recording the suite-wide audit log.
 */
abstract class KeenTestCase extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            ...parent::getPackageProviders($app),
            KeenServiceProvider::class,
        ];
    }
}
