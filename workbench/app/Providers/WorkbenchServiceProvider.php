<?php

namespace Workbench\App\Providers;

use Illuminate\Support\ServiceProvider;
use JayI\Roster\Atrium\RosterPlugin;
use Workbench\App\Models\User;

/**
 * Turns the workbench into a demo app: Roster on the workbench User, with
 * every surface switched on.
 */
class WorkbenchServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        config([
            'auth.providers.users.model' => User::class,
            'roster.users.model' => User::class,
            'roster.routes.enabled' => true,
            'roster.mcp.local.enabled' => true,
            // Atrium discovers plugins from vendor/composer/installed.json,
            // which never lists the package being developed.
            'atrium.plugins' => [RosterPlugin::class],
        ]);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
