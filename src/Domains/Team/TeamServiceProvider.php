<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Team;

use RefactorCircus\Foundation\Support\ServiceProvider;

/**
 * Teams inside an organization and their members.
 */
class TeamServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadApiRoutesFrom(__DIR__.'/routes.php');
    }
}
