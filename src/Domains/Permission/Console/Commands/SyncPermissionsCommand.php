<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Permission\Console\Commands;

use Illuminate\Console\Command;
use JayI\Roster\Domains\Role\Support\BuiltInRoles;

final class SyncPermissionsCommand extends Command
{
    protected $signature = 'roster:sync-permissions';

    protected $description = "Add Roster's built-in permissions and default roles that are missing";

    public function handle(): int
    {
        BuiltInRoles::sync();

        $this->components->info('Roster permissions and default roles are up to date.');

        return self::SUCCESS;
    }
}
