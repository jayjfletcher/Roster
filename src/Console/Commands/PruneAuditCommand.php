<?php

declare(strict_types=1);

namespace JayI\Roster\Console\Commands;

use Illuminate\Console\Command;
use JayI\Roster\Audit\AuditLog;

final class PruneAuditCommand extends Command
{
    protected $signature = 'roster:prune-audit {--days= : Keep this many days instead of roster.audit.retention_days}';

    protected $description = 'Delete audit entries older than the retention period';

    public function handle(AuditLog $log): int
    {
        $option = $this->option('days');
        $days = is_numeric($option) ? (int) $option : config('roster.audit.retention_days');

        if (! is_numeric($days)) {
            $this->components->info('Audit retention is unlimited; nothing pruned.');

            return self::SUCCESS;
        }

        $deleted = $log->prune((int) $days);

        $this->components->info("Pruned {$deleted} audit entries older than {$days} days.");

        return self::SUCCESS;
    }
}
