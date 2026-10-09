<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Roster\Domains\Organization\Actions\PurgeOrganizationAction;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\User\Actions\PurgeUserAction;
use RefactorCircus\Roster\Support\Users;

final class PurgeDeletedCommand extends Command
{
    protected $signature = 'roster:purge-deleted {--days= : Purge records deleted more than this many days ago, instead of roster.deletes.retention_days}';

    protected $description = 'Permanently delete users and organizations deleted longer ago than the retention period';

    public function handle(Users $users): int
    {
        $option = $this->option('days');
        $days = is_numeric($option) ? (int) $option : config('roster.deletes.retention_days');

        if (! is_numeric($days)) {
            $this->components->info('Keeping deleted records forever (roster.deletes.retention_days is null).');

            return self::SUCCESS;
        }

        $cutoff = Carbon::now()->subDays((int) $days);
        $purged = ['users' => 0, 'organizations' => 0];
        $skipped = 0;

        // Users first: purging them takes their personal organizations along.
        if ($users->softDeletes()) {
            $users->onlyTrashed()->where($users->newModel()->qualifyColumn('deleted_at'), '<=', $cutoff)->each(function (Model $user) use (&$purged, &$skipped): void {
                try {
                    app(PurgeUserAction::class)->execute($user);
                    $purged['users']++;
                } catch (ValidationException) {
                    $skipped++;
                }
            });
        }

        OrganizationModel::onlyTrashed()->where('deleted_at', '<=', $cutoff)->where('personal', false)->each(function (OrganizationModel $organization) use (&$purged): void {
            app(PurgeOrganizationAction::class)->execute($organization);
            $purged['organizations']++;
        });

        $this->components->info("Purged {$purged['users']} users and {$purged['organizations']} organizations deleted more than {$days} days ago.");

        if ($skipped > 0) {
            $this->components->warn("Kept {$skipped} deleted users who still own shared organizations.");
        }

        return self::SUCCESS;
    }
}
