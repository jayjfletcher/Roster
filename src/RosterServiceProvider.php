<?php

declare(strict_types=1);

namespace JayI\Roster;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use JayI\Foundation\Audit\AuditHooks;
use JayI\Foundation\Audit\History;
use JayI\Foundation\Packages\Package;
use JayI\Foundation\Support\PackageServiceProvider;
use JayI\Foundation\Support\Surface;
use JayI\Roster\Atrium\ScreenAccess;
use JayI\Roster\Console\Commands\PurgeDeletedCommand;
use JayI\Roster\Domains\DomainServiceProvider;
use JayI\Roster\Domains\Permission\Services\Authorizer;
use JayI\Roster\Mcp\RosterServer;
use JayI\Roster\Support\Audit;
use JayI\Roster\Support\Users;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Roster's package-wide wiring: config, views, translations, publish tags,
 * the MCP server, Cortex, the rate limiter and the Atrium extras. Each domain
 * under `Domains/` registers its own bindings, routes, listeners, commands
 * and morph aliases through its provider, listed in DomainServiceProvider.
 *
 * Roster describes itself to jayi/foundation in `definition()`, so the shared
 * base classes find its config, routes and MCP server by namespace.
 */
class RosterServiceProvider extends PackageServiceProvider
{
    /**
     * Roster checks every call by default: `roster.authorization` is on
     * unless an application turns it off. The shared history route and tool
     * need `roster.audit.view` globally.
     */
    protected function definition(): Package
    {
        return Package::make('roster', __NAMESPACE__)
            ->label('Roster')
            ->server(RosterServer::class)
            ->authorization()
            ->authorizeHistory(fn (?Authenticatable $user): bool => $this->app->make(Authorizer::class)
                ->check($user instanceof Model ? $user : null, 'roster.audit.view'));
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/roster.php', 'roster');
        $this->registerPackage();

        $this->app->scoped(Roster::class);
        $this->app->singleton(Users::class);

        $this->app->register(DomainServiceProvider::class);

        $this->registerSurfaces();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Cortex is optional: agents get the Roster tools only when it is loaded.
        $this->registerCortex();

        $this->registerRateLimiter();
        $this->registerNotFoundResponses();

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'roster');

        // @rosterCan('roster.users.update', $scope, $self) ... @endrosterCan:
        // the screens' own check, so a control shows only when its action is allowed.
        Blade::if('rosterCan', ScreenAccess::allows(...));

        $this->loadTranslationsFrom(__DIR__.'/../lang', 'roster');

        $this->registerMcpServer();
        $this->loadHistoryRoutes();
        $this->registerAudit();

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
     * Roster's routes outside its JSON API: SCIM, and the signed pages a
     * person opens from an email. The shared surface is scoped, so name them
     * each time it is resolved.
     */
    private function registerSurfaces(): void
    {
        $this->app->afterResolving(Surface::class, function (Surface $surface): void {
            $surface->route('roster.scim.', 'scim')
                ->route('roster.invitations.page', 'web')
                ->route('roster.invitations.show', 'web')
                ->route('roster.impersonation.', 'web');
        });
    }

    /**
     * Teach the suite-wide audit log (jayi/keen) about Roster's models and
     * events, whether or not it is installed. Reading the whole log needs
     * Roster's global `roster.audit.view` unless the application defines
     * `viewAuditLog` itself; checked once the application has booted, so a
     * definition in its own providers wins.
     */
    private function registerAudit(): void
    {
        $this->app->make(Audit::class)->register($this->app->make(AuditHooks::class));

        $this->app->booted(function (): void {
            if (Gate::has(History::ABILITY)) {
                return;
            }

            Gate::define(History::ABILITY, fn (Model $user, mixed ...$arguments): bool => $this->app->make(Authorizer::class)->check($user, 'roster.audit.view'));
        });
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
}
