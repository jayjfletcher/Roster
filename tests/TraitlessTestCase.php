<?php

declare(strict_types=1);

namespace JayI\Roster\Tests;

use JayI\Roster\Tests\Fixtures\PlainUser;

/**
 * A host user model without the HasRoster trait. The relation must be
 * registered at boot, so the model is configured before the app is created.
 */
abstract class TraitlessTestCase extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('roster.users.model', PlainUser::class);
    }
}
