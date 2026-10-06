<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Role;

use JayI\Foundation\Support\ServiceProvider;
use JayI\Roster\Domains\Role\Console\Commands\GrantSuperAdminCommand;
use JayI\Roster\Domains\Role\Models\RoleAssignmentModel;
use JayI\Roster\Domains\Role\Models\RoleModel;

/**
 * Roles and the role assignments that grant them to users.
 */
class RoleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->keepMorphAliases([
            'JayI\Roster\Models\Role' => RoleModel::class,
            'JayI\Roster\Models\RoleAssignment' => RoleAssignmentModel::class,
        ]);

        $this->loadApiRoutesFrom(__DIR__.'/routes.php');

        if ($this->app->runningInConsole()) {
            $this->commands([GrantSuperAdminCommand::class]);
        }
    }
}
