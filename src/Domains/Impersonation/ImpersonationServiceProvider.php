<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Impersonation;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Http\Kernel;
use Illuminate\Routing\Router;
use JayI\Foundation\Support\ServiceProvider;
use JayI\Roster\Domains\Impersonation\Http\Middleware\SyncImpersonation;
use JayI\Roster\Domains\Impersonation\Models\ImpersonationModel;
use JayI\Roster\Domains\Impersonation\Services\ImpersonationContext;
use JayI\Roster\Domains\Impersonation\Services\Impersonator;

/**
 * Signing in as another user, and the way back.
 */
class ImpersonationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(ImpersonationContext::class);
        $this->app->scoped(Impersonator::class);
    }

    public function boot(): void
    {
        $this->keepMorphAliases([
            'JayI\Roster\Models\Impersonation' => ImpersonationModel::class,
        ]);

        $this->registerMiddleware();

        $this->loadApiRoutesFrom(__DIR__.'/routes.php');
        $this->loadRoutesFrom(__DIR__.'/web.php');
    }

    private function registerMiddleware(): void
    {
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('roster.impersonation', SyncImpersonation::class);

        $group = $this->app->make(Repository::class)->get('roster.impersonation.middleware_group');

        if (! is_string($group) || $group === '') {
            return;
        }

        // Through the HTTP kernel: it re-syncs its groups onto the router
        // on every request, dropping anything pushed onto the router alone.
        $this->callAfterResolving(HttpKernel::class, function (mixed $kernel) use ($group): void {
            if ($kernel instanceof Kernel) {
                $kernel->appendMiddlewareToGroup($group, SyncImpersonation::class);
            }
        });

        $router->pushMiddlewareToGroup($group, SyncImpersonation::class);
    }
}
