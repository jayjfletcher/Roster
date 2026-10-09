<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Role;

use RefactorCircus\Keystone\Support\ServiceProvider;
use RefactorCircus\Roster\Domains\Role\Console\Commands\GrantSuperAdminCommand;

/**
 * Roles and the role assignments that grant them to users.
 */
class RoleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadApiRoutesFrom(__DIR__.'/routes.php');

        if ($this->app->runningInConsole()) {
            $this->commands([GrantSuperAdminCommand::class]);
        }
    }
}
