<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Tests;

use RefactorCircus\Keen\KeenServiceProvider;

/**
 * Roster booted with refactor-circus/keen recording the suite-wide audit log.
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
