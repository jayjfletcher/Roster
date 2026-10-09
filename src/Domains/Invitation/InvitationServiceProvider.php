<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Invitation;

use RefactorCircus\Keystone\Support\ServiceProvider;

/**
 * Email invitations to an organization, and the page they link to.
 */
class InvitationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadApiRoutesFrom(__DIR__.'/routes.php');
        $this->loadRoutesFrom(__DIR__.'/web.php');
    }
}
