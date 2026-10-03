<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Team;

use JayI\Roster\Domains\Team\Models\TeamMemberModel;
use JayI\Roster\Domains\Team\Models\TeamModel;
use JayI\Roster\Support\ServiceProvider;

/**
 * Teams inside an organization and their members.
 */
class TeamServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->keepMorphAliases([
            'JayI\Roster\Models\Team' => TeamModel::class,
            'JayI\Roster\Models\TeamMember' => TeamMemberModel::class,
        ]);

        $this->loadApiRoutesFrom(__DIR__.'/routes.php');
    }
}
