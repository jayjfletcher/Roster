<?php

declare(strict_types=1);

namespace JayI\Roster;

use Illuminate\Auth\Events\Verified;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Foundation\Http\Kernel;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use JayI\Impex\Events\RunFailed;
use JayI\Impex\Flows\FlowRegistry;
use JayI\Roster\Access\Authorizer;
use JayI\Roster\Access\Permissions;
use JayI\Roster\Audit\AuditLog;
use JayI\Roster\Audit\AuditRecorder;
use JayI\Roster\Audit\Surface;
use JayI\Roster\Console\Commands\GrantSuperAdminCommand;
use JayI\Roster\Console\Commands\PruneAuditCommand;
use JayI\Roster\Console\Commands\PruneTransfersCommand;
use JayI\Roster\Console\Commands\SyncPermissionsCommand;
use JayI\Roster\Console\Commands\VerifyAuditCommand;
use JayI\Roster\Contracts\ActionFinishedEvent;
use JayI\Roster\Contracts\ActionStartingEvent;
use JayI\Roster\Cortex\CortexIntegration;
use JayI\Roster\Enums\TransferStatus;
use JayI\Roster\Http\Middleware\EnsureUserHasOrganization;
use JayI\Roster\Http\Middleware\EnsureUserIsActive;
use JayI\Roster\Http\Middleware\SyncImpersonation;
use JayI\Roster\Impersonation\ImpersonationContext;
use JayI\Roster\Impersonation\Impersonator;
use JayI\Roster\Listeners\JoinOrganizationsOnVerified;
use JayI\Roster\Mcp\RosterServer;
use JayI\Roster\Models\Membership;
use JayI\Roster\Models\Organization;
use JayI\Roster\Models\Profile;
use JayI\Roster\Models\RoleAssignment;
use JayI\Roster\Models\Team;
use JayI\Roster\Models\Transfer;
use JayI\Roster\Scim\ScimContext;
use JayI\Roster\Sso\Sso;
use JayI\Roster\Support\Users;
use JayI\Roster\Transfers\Flows\ExportFlow;
use JayI\Roster\Transfers\Flows\ImportFlow;
use JayI\Roster\Transfers\TransferContext;
use JayI\Roster\Transfers\Transfers;
use Laravel\Mcp\Facades\Mcp;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RosterServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/roster.php', 'roster');

        $this->app->singleton(Roster::class);
        $this->app->singleton(Users::class);
        $this->app->scoped(Permissions::class);
        $this->app->scoped(AuditRecorder::class);
        $this->app->scoped(Surface::class);
        $this->app->scoped(ImpersonationContext::class);
        $this->app->scoped(ScimContext::class);
        $this->app->scoped(TransferContext::class);
        $this->app->scoped(Impersonator::class);
        $this->app->singleton(AuditLog::class);
        $this->app->singleton(Authorizer::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Cortex is optional: agents get the Roster tools only when it is loaded.
        $this->app->make(CortexIntegration::class)->register();

        $this->registerProfileRelation();

        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('roster.active', EnsureUserIsActive::class);
        $router->aliasMiddleware('roster.organization', EnsureUserHasOrganization::class);
        $router->aliasMiddleware('roster.impersonation', SyncImpersonation::class);

        $group = $this->app->make(Repository::class)->get('roster.impersonation.middleware_group');

        if (is_string($group) && $group !== '') {
            // Through the HTTP kernel: it re-syncs its groups onto the router
            // on every request, dropping anything pushed onto the router alone.
            $this->callAfterResolving(HttpKernel::class, function (mixed $kernel) use ($group): void {
                if ($kernel instanceof Kernel) {
                    $kernel->appendMiddlewareToGroup($group, SyncImpersonation::class);
                }
            });

            $router->pushMiddlewareToGroup($group, SyncImpersonation::class);
        }

        $this->app->make(Dispatcher::class)->listen(Verified::class, JoinOrganizationsOnVerified::class);

        $this->registerGate();
        $this->registerAuditLog();
        $this->registerTransfers();
        $this->registerRateLimiter();
        $this->registerNotFoundResponses();

        $this->loadRoutesFrom(__DIR__.'/../routes/roster.php');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadRoutesFrom(__DIR__.'/../routes/scim.php');

        if ($this->app->make(Sso::class)->available()) {
            $this->loadRoutesFrom(__DIR__.'/../routes/sso.php');

            // SAML identity providers post the signed assertion cross-site;
            // the assertion's signature, not a CSRF token, protects it.
            ValidateCsrfToken::except([trim((string) $this->app->make(Repository::class)->get('roster.sso.prefix', 'roster/sso'), '/').'/*/callback']);
        }

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'roster');
        Blade::anonymousComponentPath(__DIR__.'/../resources/views/components', 'roster');

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

        $this->commands([
            GrantSuperAdminCommand::class,
            PruneAuditCommand::class,
            PruneTransfersCommand::class,
            SyncPermissionsCommand::class,
            VerifyAuditCommand::class,
        ]);

        // Opt-in only, and deliberately not under the `roster` umbrella tag:
        // most apps already have a users table.
        $this->publishesMigrations([
            __DIR__.'/../database/migrations/users' => database_path('migrations'),
        ], 'roster-users-migration');
    }

    /**
     * Give a user model without the HasRoster trait the same relations, so
     * Roster works on any model untouched.
     */
    private function registerProfileRelation(): void
    {
        $model = $this->app->make(Repository::class)->get('roster.users.model');

        if (! is_string($model) || ! is_subclass_of($model, Model::class) || method_exists($model, 'rosterProfile')) {
            return;
        }

        $model::resolveRelationUsing(
            'rosterProfile',
            fn (Model $user) => $user->hasOne(Profile::class, 'user_id'),
        );

        $model::resolveRelationUsing(
            'rosterMemberships',
            fn (Model $user) => $user->hasMany(Membership::class, 'user_id'),
        );

        $model::resolveRelationUsing(
            'rosterRoleAssignments',
            fn (Model $user) => $user->hasMany(RoleAssignment::class, 'user_id'),
        );

        $model::resolveRelationUsing(
            'rosterOrganizations',
            fn (Model $user) => $user->belongsToMany(Organization::class, 'roster_memberships', 'user_id', 'organization_id')->withTimestamps(),
        );
    }

    /**
     * Resolve Roster permissions through Laravel's Gate, so `$user->can()`
     * and `@can` work with them, and give Atrium a gate when the app has not
     * defined one.
     */
    private function registerGate(): void
    {
        $gate = $this->app->make(Gate::class);

        $gate->before(function (mixed $user, string $ability, array $arguments): ?bool {
            if (! $user instanceof Model) {
                return null;
            }

            // Refused while impersonating, whoever is really at the keyboard.
            if ($this->app->make(ImpersonationContext::class)->isBlocked($ability)) {
                return false;
            }

            $permissions = $this->app->make(Permissions::class);

            if ($permissions->isSuperAdmin($user)) {
                return true;
            }

            if (! $permissions->knows($ability)) {
                return null;
            }

            $scope = $this->scopeFrom($arguments) ?? $permissions->contextScope($user);

            // Null rather than false: the app's own gates and policies still
            // get their say when the user lacks the permission.
            return $permissions->allows($user, $ability, $scope) ? true : null;
        });

        $this->app->booted(function () use ($gate): void {
            $ability = $this->app->make(Repository::class)->get('atrium.gate', 'viewAtrium');

            if (is_string($ability) && ! $gate->has($ability)) {
                $gate->define($ability, fn (mixed $user): bool => $user instanceof Model
                    && $this->app->make(Permissions::class)->allows($user, 'atrium.view'));
            }
        });
    }

    /**
     * Register the import and export flows with Impex, when it is installed,
     * and mark a transfer failed if its run fails.
     */
    private function registerTransfers(): void
    {
        if (! $this->app->make(Transfers::class)->available()) {
            return;
        }

        $this->callAfterResolving(FlowRegistry::class, function (FlowRegistry $flows): void {
            $flows->registerMany([
                Transfers::IMPORT_FLOW => ImportFlow::class,
                Transfers::EXPORT_FLOW => ExportFlow::class,
            ]);
        });

        $this->app->make(Dispatcher::class)->listen(RunFailed::class, function (RunFailed $event): void {
            Transfer::query()
                ->where('impex_run_id', $event->runId)
                ->whereNotIn('status', [TransferStatus::Completed, TransferStatus::Cancelled, TransferStatus::Expired])
                ->update(['status' => TransferStatus::Failed, 'finished_at' => now()]);
        });
    }

    /**
     * Record every Roster change from the Action events, unless turned off.
     */
    private function registerAuditLog(): void
    {
        if ($this->app->make(Repository::class)->get('roster.audit.enabled') !== true) {
            return;
        }

        $events = $this->app->make(Dispatcher::class);

        $events->listen(ActionStartingEvent::class, fn (ActionStartingEvent $event) => $this->app->make(AuditRecorder::class)->starting($event));
        $events->listen(ActionFinishedEvent::class, fn (ActionFinishedEvent $event) => $this->app->make(AuditRecorder::class)->finished($event));
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

    /**
     * The organization or team a Gate check is about.
     *
     * @param  array<int|string, mixed>  $arguments
     */
    private function scopeFrom(array $arguments): Organization|Team|null
    {
        foreach ($arguments as $argument) {
            if ($argument instanceof Organization || $argument instanceof Team) {
                return $argument;
            }

            if ($argument instanceof Model && method_exists($argument, 'organization')) {
                $organization = $argument->getRelationValue('organization');

                if ($organization instanceof Organization) {
                    return $organization;
                }
            }
        }

        return null;
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
