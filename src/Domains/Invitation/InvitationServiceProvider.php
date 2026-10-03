<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Invitation;

use JayI\Roster\Domains\Invitation\Models\InvitationModel;
use JayI\Roster\Support\ServiceProvider;

/**
 * Email invitations to an organization, and the page they link to.
 */
class InvitationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->keepMorphAliases([
            'JayI\Roster\Models\Invitation' => InvitationModel::class,
        ]);

        $this->loadApiRoutesFrom(__DIR__.'/routes.php');
        $this->loadRoutesFrom(__DIR__.'/web.php');
    }
}
