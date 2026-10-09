<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Tests;

/**
 * Roster with authorization on, as shipped.
 */
abstract class AuthorizationTestCase extends TestCase
{
    // Atrium's gate comes from Roster's `atrium.view` permission, as shipped.
    protected bool $openAtrium = false;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('roster.authorization', true);
    }
}
