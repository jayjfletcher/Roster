<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Transfer;

use Illuminate\Contracts\Events\Dispatcher;
use JayI\Impex\Domains\Flow\Services\FlowRegistry;
use JayI\Impex\Domains\Run\Events\RunFailed;
use JayI\Roster\Domains\Transfer\Console\Commands\PruneTransfersCommand;
use JayI\Roster\Domains\Transfer\Enums\TransferStatus;
use JayI\Roster\Domains\Transfer\Models\TransferModel;
use JayI\Roster\Domains\Transfer\Models\TransferRowModel;
use JayI\Roster\Domains\Transfer\Services\PlanCache;
use JayI\Roster\Domains\Transfer\Services\TransferContext;
use JayI\Roster\Domains\Transfer\Services\Transfers;
use JayI\Roster\Support\ServiceProvider;
use JayI\Roster\Transfers\Flows\ExportFlow;
use JayI\Roster\Transfers\Flows\ImportFlow;

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
        $this->keepMorphAliases([
            'JayI\Roster\Models\Transfer' => TransferModel::class,
            'JayI\Roster\Models\TransferRow' => TransferRowModel::class,
        ]);

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
     * The flows keep their `JayI\Roster\Transfers\Flows` class names: Impex
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
