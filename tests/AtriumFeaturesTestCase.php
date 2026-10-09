<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Tests;

/**
 * Roster booted behind an Atrium feature, `roster.atrium.features`.
 */
abstract class AtriumFeaturesTestCase extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('roster.atrium.features', ['roster']);
    }
}
