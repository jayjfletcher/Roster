<?php

declare(strict_types=1);

namespace JayI\Roster;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use JayI\Atrium\Facades\Atrium;
use JayI\Roster\Atrium\ScreenAccess;
use JayI\Roster\Console\Commands\PurgeDeletedCommand;
use JayI\Roster\Cortex\CortexIntegration;
use JayI\Roster\Domains\DomainServiceProvider;
use JayI\Roster\Mcp\RosterServer;
use JayI\Roster\Support\Users;
use Laravel\Mcp\Facades\Mcp;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Roster's package-wide wiring: config, views, translations, publish tags,
 * the MCP server, Cortex, the rate limiter and the Atrium extras. Each domain
 * under `Domains/` registers its own bindings, routes, listeners, commands
 * and morph aliases through its provider, listed in DomainServiceProvider.
 */
class RosterServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/roster.php', 'roster');

        $this->app->scoped(Roster::class);
        $this->app->singleton(Users::class);

        $this->app->register(DomainServiceProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Cortex is optional: agents get the Roster tools only when it is loaded.
        $this->app->make(CortexIntegration::class)->register();

        $this->registerRateLimiter();
        $this->registerNotFoundResponses();

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'roster');
        Blade::anonymousComponentPath(__DIR__.'/../resources/views/components', 'roster');

        // Utilities Roster's screens use that Atrium's stylesheet lacks.
        Atrium::css((string) file_get_contents(__DIR__.'/../resources/css/atrium.css'), 'roster');

        // @rosterCan('roster.users.update', $scope, $self) ... @endrosterCan:
        // the screens' own check, so a control shows only when its action is allowed.
        Blade::if('rosterCan', ScreenAccess::allows(...));

        $this->loadTranslationsFrom(__DIR__.'/../lang', 'roster');

        $this->registerMcpServer();

        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/roster.php' => config_path('roster.php'),
        ], ['roster', 'roster-config']);

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/roster'),
        ], ['roster', 'roster-views']);

        $this->publishes([
            __DIR__.'/../lang' => $this->app->langPath('vendor/roster'),
        ], ['roster', 'roster-lang']);

        $this->publishesMigrations([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], ['roster', 'roster-migrations']);

        // Published copies are served instead of these; see Transfers::template().
        $this->publishes([
            __DIR__.'/../resources/import-templates' => resource_path('roster/import-templates'),
        ], ['roster', 'roster-import-templates']);

        $this->commands([PurgeDeletedCommand::class]);

        // Opt-in only, and deliberately not under the `roster` umbrella tag:
        // most apps already have a users table.
        $this->publishesMigrations([
            __DIR__.'/../database/migrations/users' => database_path('migrations'),
        ], 'roster-users-migration');
    }

    /**
     * The `roster` limiter behind `throttle:roster`: per user, or per IP for
     * guests. A null `roster.rate_limit.per_minute` turns it off.
     */
    private function registerRateLimiter(): void
    {
        RateLimiter::for('roster', function (Request $request): Limit {
            $perMinute = $this->app->make(Repository::class)->get('roster.rate_limit.per_minute');

            if (! is_numeric($perMinute)) {
                return Limit::none();
            }

            $user = $request->user();

            return Limit::perMinute((int) $perMinute)->by($user instanceof Model ? 'user:'.$user->getKey() : 'ip:'.$request->ip());
        });
    }

    /**
     * Missing records on Roster's own routes answer a plain 404. Laravel's
     * default message names the model class, which callers need not see.
     */
    private function registerNotFoundResponses(): void
    {
        $this->callAfterResolving(ExceptionHandler::class, function (mixed $handler): void {
            // Apps that swap in their own handler keep Laravel's responses.
            if (! $handler instanceof Handler) {
                return;
            }

            $handler->renderable(function (NotFoundHttpException $exception, Request $request): ?JsonResponse {
                $name = $request->route()?->getName();

                if (! is_string($name) || ! str_starts_with($name, 'roster.') || ! $exception->getPrevious() instanceof ModelNotFoundException) {
                    return null;
                }

                return new JsonResponse(['message' => __('roster::roster.not_found')], 404);
            });
        });
    }

    private function registerMcpServer(): void
    {
        if (! class_exists(Mcp::class)) {
            return;
        }

        $config = $this->app->make(Repository::class);

        if ($config->get('roster.mcp.web.enabled') === true) {
            /** @var array<int, string> $middleware */
            $middleware = $config->get('roster.mcp.web.middleware', []);

            Mcp::web((string) $config->get('roster.mcp.web.route'), RosterServer::class)
                ->middleware($middleware);
        }

        if ($config->get('roster.mcp.local.enabled') === true) {
            Mcp::local((string) $config->get('roster.mcp.local.handle'), RosterServer::class);
        }
    }
}
