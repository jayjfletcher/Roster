<?php

declare(strict_types=1);

namespace JayI\Roster\Tests;

use Illuminate\Support\Facades\Gate;
use JayI\Atrium\AtriumServiceProvider;
use JayI\Impex\ImpexServiceProvider;
use JayI\Roster\Atrium\RosterPlugin;
use JayI\Roster\RosterServiceProvider;
use Laravel\Mcp\Server\McpServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Workbench\App\Models\User;

abstract class TestCase extends Orchestra
{
    /**
     * Whether to open Atrium to everyone, as surface tests need. Atrium
     * denies everything outside `local` until its gate is defined.
     */
    protected bool $openAtrium = true;

    protected function setUp(): void
    {
        parent::setUp();

        if ($this->openAtrium) {
            Gate::define('viewAtrium', fn (): bool => true);
        }
    }

    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            McpServiceProvider::class,
            AtriumServiceProvider::class,
            ImpexServiceProvider::class,
            RosterServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing.foreign_key_constraints', true);
        $app['config']->set('cache.default', 'array');
        $app['config']->set('roster.users.model', User::class);
        // Surface tests need the API mounted; DefaultsTest covers it being off.
        $app['config']->set('roster.routes.enabled', true);
        // Surface tests cover behaviour; tests/Authorization turns it back on.
        $app['config']->set('roster.authorization', false);
        // Discovery reads vendor/composer/installed.json, which never lists
        // the package under test, so register the plugin the way an app would.
        $app['config']->set('atrium.plugins', [RosterPlugin::class]);
        // Impex runs drive inline on the sync queue, so a CSV import or
        // export completes (or parks on its confirmation) within the call.
        $app['config']->set('queue.default', 'sync');
        $app['config']->set('filesystems.disks.local.root', sys_get_temp_dir().'/roster-tests-'.getmypid());
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadLaravelMigrations();

        $this->loadMigrationsFrom(dirname(__DIR__).'/database/migrations');
        // Impex's migrations are publish-only; jayi/impex is a dev dependency.
        $this->loadMigrationsFrom(dirname(__DIR__).'/vendor/jayi/impex/database/migrations');
    }
}
