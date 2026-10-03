<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Audit;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use JayI\Roster\Contracts\ActionFinishedEvent;
use JayI\Roster\Contracts\ActionStartingEvent;
use JayI\Roster\Domains\Audit\Console\Commands\PruneAuditCommand;
use JayI\Roster\Domains\Audit\Console\Commands\VerifyAuditCommand;
use JayI\Roster\Domains\Audit\Models\AuditEntryModel;
use JayI\Roster\Domains\Audit\Services\AuditLog;
use JayI\Roster\Domains\Audit\Services\AuditRecorder;
use JayI\Roster\Domains\Audit\Services\Surface;
use JayI\Roster\Support\ServiceProvider;

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
