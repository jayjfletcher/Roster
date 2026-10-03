<?php

declare(strict_types=1);

namespace JayI\Roster\Support;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\CachesRoutes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

/**
 * Base class for the Roster domain service providers.
 *
 * Every JSON API route shares one group - the configured prefix and
 * middleware and the `roster.` name prefix - so each domain loads its routes
 * file through `loadApiRoutesFrom()` rather than building the group itself.
 */
abstract class ServiceProvider extends BaseServiceProvider
{
    /**
     * Load a routes file inside the JSON API's route group.
     *
     * Off unless explicitly enabled: the configured middleware is what
     * authenticates these routes.
     */
    protected function loadApiRoutesFrom(string $path): void
    {
        $config = $this->app->make(Repository::class);

        if ($config->get('roster.routes.enabled') !== true || $this->routesAreCached()) {
            return;
        }

        /** @var array<int, string> $middleware */
        $middleware = $config->get('roster.routes.middleware', []);

        Route::prefix((string) $config->get('roster.routes.prefix', 'roster'))
            ->middleware($middleware)
            ->name('roster.')
            ->group($path);
    }

    /**
     * Keep the class names models were stored under before they moved into
     * their domain, so audit subjects, Pennant scopes or any `*_type` column
     * an application wrote with the old names still resolve - and new
     * records keep writing the same value, which the audit hash chain needs.
     *
     * @param  array<string, class-string<Model>>  $map
     */
    protected function keepMorphAliases(array $map): void
    {
        Relation::morphMap($map);
    }

    protected function routesAreCached(): bool
    {
        return $this->app instanceof CachesRoutes && $this->app->routesAreCached();
    }
}
