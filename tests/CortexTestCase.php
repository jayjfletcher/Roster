<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Tests;

use Laravel\Ai\AiServiceProvider;
use RefactorCircus\Cortex\CortexServiceProvider;

/**
 * Roster with Cortex installed and loaded, authorization on.
 */
abstract class CortexTestCase extends AuthorizationTestCase
{
    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            AiServiceProvider::class,
            ...parent::getPackageProviders($app),
            CortexServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('cortex.cache.store', 'array');
    }

    protected function migrationPaths(): array
    {
        return [...parent::migrationPaths(), dirname(__DIR__).'/vendor/refactor-circus/cortex/database/migrations'];
    }
}
