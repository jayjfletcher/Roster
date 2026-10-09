<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Permission;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Foundation\Contracts\ActionFinishedEvent;
use RefactorCircus\Foundation\Support\ServiceProvider;
use RefactorCircus\Roster\Domains\Impersonation\Services\ImpersonationContext;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Permission\Console\Commands\SyncPermissionsCommand;
use RefactorCircus\Roster\Domains\Permission\Services\Authorizer;
use RefactorCircus\Roster\Domains\Permission\Services\Permissions;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;
use RefactorCircus\Roster\Roster;

/**
 * Permissions, how they are checked, and Roster's place in Laravel's Gate.
 */
class PermissionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(Permissions::class);
        // Scoped like the Permissions it uses, so a long-lived worker (Octane,
        // queues) never keeps one request's resolved permissions.
        $this->app->scoped(Authorizer::class);
    }

    public function boot(): void
    {
        $this->registerGate();

        $this->loadApiRoutesFrom(__DIR__.'/routes.php');

        if ($this->app->runningInConsole()) {
            $this->commands([SyncPermissionsCommand::class]);
        }
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

        // Permissions and the current organization/team are remembered for the
        // request; anything that changes them (assigning a role, switching
        // context, joining) clears that, so later checks see the change.
        $this->app->make(Dispatcher::class)->listen(ActionFinishedEvent::class, function (ActionFinishedEvent $event): void {
            if (preg_match('/(Listed|Shown)ActionEvent$/', $event::class) === 1) {
                return;
            }

            if ($this->app->resolved(Permissions::class)) {
                $this->app->make(Permissions::class)->flush();
            }

            if ($this->app->resolved(Roster::class)) {
                $this->app->make(Roster::class)->forget();
            }
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
     * The organization or team a Gate check is about.
     *
     * @param  array<int|string, mixed>  $arguments
     */
    private function scopeFrom(array $arguments): OrganizationModel|TeamModel|null
    {
        foreach ($arguments as $argument) {
            if ($argument instanceof OrganizationModel || $argument instanceof TeamModel) {
                return $argument;
            }

            if ($argument instanceof Model && method_exists($argument, 'organization')) {
                $organization = $argument->getRelationValue('organization');

                if ($organization instanceof OrganizationModel) {
                    return $organization;
                }
            }
        }

        return null;
    }
}
