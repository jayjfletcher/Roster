<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Audit;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use JayI\Foundation\Contracts\ActionFinishedEvent;
use JayI\Foundation\Contracts\ActionStartingEvent;
use JayI\Foundation\Support\ServiceProvider;
use JayI\Foundation\Support\Surface as SharedSurface;
use JayI\Roster\Domains\Audit\Console\Commands\PruneAuditCommand;
use JayI\Roster\Domains\Audit\Console\Commands\VerifyAuditCommand;
use JayI\Roster\Domains\Audit\Models\AuditEntryModel;
use JayI\Roster\Domains\Audit\Services\AuditLog;
use JayI\Roster\Domains\Audit\Services\AuditRecorder;
use JayI\Roster\Domains\Audit\Services\Surface;

/**
 * The hash-chained audit log, recorded from the Action events.
 */
class AuditServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(AuditRecorder::class);
        $this->app->scoped(Surface::class);
        $this->app->singleton(AuditLog::class);

        // Roster's routes outside its JSON API: SCIM, and the signed pages
        // a person opens from an email. The shared surface is scoped, so
        // name them each time it is resolved.
        $this->app->afterResolving(SharedSurface::class, function (SharedSurface $surface): void {
            $surface->route('roster.scim.', 'scim')
                ->route('roster.invitations.page', 'web')
                ->route('roster.invitations.show', 'web')
                ->route('roster.impersonation.', 'web');
        });
    }

    public function boot(): void
    {
        $this->keepMorphAliases([
            'JayI\Roster\Models\AuditEntry' => AuditEntryModel::class,
        ]);

        $this->registerAuditLog();

        $this->loadApiRoutesFrom(__DIR__.'/routes.php');

        if ($this->app->runningInConsole()) {
            $this->commands([PruneAuditCommand::class, VerifyAuditCommand::class]);
        }
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
}
