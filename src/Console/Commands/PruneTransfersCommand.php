<?php

declare(strict_types=1);

namespace JayI\Roster\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use JayI\Roster\Models\Transfer;
use JayI\Roster\Transfers\Transfers;

final class PruneTransfersCommand extends Command
{
    protected $signature = 'roster:prune-transfers {--days= : Keep this many days instead of roster.transfers.retention_days}';

    protected $description = 'Delete the files of old imports and exports';

    public function handle(Transfers $transfers): int
    {
        $option = $this->option('days');
        $days = is_numeric($option) ? (int) $option : (int) config('roster.transfers.retention_days', 7);
        $pruned = 0;

        Transfer::query()
            ->where('created_at', '<', now()->subDays($days))
            ->where(fn (Builder $query): Builder => $query->whereNotNull('input_path')->orWhereNotNull('output_path'))
            ->each(function (Transfer $transfer) use ($transfers, &$pruned): void {
                $transfers->disk()->deleteDirectory('roster/transfers/'.$transfer->id);
                $transfer->update(['input_path' => null, 'output_path' => null]);
                $pruned++;
            });

        $this->components->info("Deleted the files of {$pruned} transfers older than {$days} days.");

        return self::SUCCESS;
    }
}
