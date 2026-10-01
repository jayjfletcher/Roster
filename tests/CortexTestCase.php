<?php

declare(strict_types=1);

namespace JayI\Roster\Tests;

use JayI\Cortex\CortexServiceProvider;
use Laravel\Ai\AiServiceProvider;

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

    protected function defineDatabaseMigrations(): void
    {
        parent::defineDatabaseMigrations();

        $this->loadMigrationsFrom(dirname(__DIR__).'/vendor/jayi/cortex/database/migrations');
    }
}
