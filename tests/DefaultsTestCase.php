<?php

declare(strict_types=1);

namespace JayI\Roster\Tests;

/**
 * Boots Roster with its shipped config untouched, for the defaults the
 * package promises.
 */
abstract class DefaultsTestCase extends TestCase
{
    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('database.default', 'testing');
    }
}
