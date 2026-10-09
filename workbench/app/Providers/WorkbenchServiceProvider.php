<?php

namespace Workbench\App\Providers;

use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Http\Kernel;
use Illuminate\Support\ServiceProvider;
use JayI\Roster\Atrium\RosterPlugin;
use Workbench\App\Http\Middleware\SignInWorkbenchUser;
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
            // jayi/pennantplus's layered store: users who follow a feature's
            // global value store nothing, as in a real application.
            'pennant.default' => 'pennantplus',
            'pennant.stores.pennantplus' => [
                'driver' => 'pennantplus',
                'connection' => null,
                'table' => 'features',
            ],
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
        // Keep the workbench user signed in whatever URL is opened first.
        $this->callAfterResolving(HttpKernel::class, function (HttpKernel $kernel): void {
            if ($kernel instanceof Kernel) {
                $kernel->appendMiddlewareToGroup('web', SignInWorkbenchUser::class);
            }
        });

        //
    }
}
