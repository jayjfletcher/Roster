<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Transfer;

use Illuminate\Contracts\Events\Dispatcher;
use RefactorCircus\Impex\Domains\Flow\Services\FlowRegistry;
use RefactorCircus\Impex\Domains\Run\Events\RunFailed;
use RefactorCircus\Keystone\Support\ServiceProvider;
use RefactorCircus\Roster\Domains\Transfer\Console\Commands\PruneTransfersCommand;
use RefactorCircus\Roster\Domains\Transfer\Enums\TransferStatus;
use RefactorCircus\Roster\Domains\Transfer\Models\TransferModel;
use RefactorCircus\Roster\Domains\Transfer\Services\PlanCache;
use RefactorCircus\Roster\Domains\Transfer\Services\TransferContext;
use RefactorCircus\Roster\Domains\Transfer\Services\Transfers;
use RefactorCircus\Roster\Transfers\Flows\ExportFlow;
use RefactorCircus\Roster\Transfers\Flows\ImportFlow;

/**
 * CSV imports and exports, run as Impex flows.
 */
class TransferServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(TransferContext::class);
        $this->app->singleton(PlanCache::class);
    }

    public function boot(): void
    {
        $this->registerTransfers();

        $this->loadApiRoutesFrom(__DIR__.'/routes.php');
        $this->loadRoutesFrom(__DIR__.'/web.php');

        if ($this->app->runningInConsole()) {
            $this->commands([PruneTransfersCommand::class]);
        }
    }

    /**
     * Register the import and export flows with Impex, when it is installed,
     * and mark a transfer failed if its run fails.
     *
     * The flows keep their `RefactorCircus\Roster\Transfers\Flows` class names: Impex
     * stores them on every run and batch, and replays in-flight runs by them.
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
            TransferModel::query()
                ->where('impex_run_id', $event->runId)
                ->whereNotIn('status', [TransferStatus::Completed, TransferStatus::Cancelled, TransferStatus::Expired])
                ->update(['status' => TransferStatus::Failed, 'finished_at' => now()]);
        });
    }
}
