<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Listeners;

use Illuminate\Auth\Events\Verified;
use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Roster\Domains\Organization\Actions\JoinOrganizationsByDomainAction;
use RefactorCircus\Roster\Support\Users;

/**
 * Runs domain auto-join the moment a user verifies their email.
 */
final class JoinOrganizationsOnVerified
{
    public function handle(Verified $event): void
    {
        $user = $event->user;

        if ($user instanceof Model && is_a($user, app(Users::class)->model())) {
            app(JoinOrganizationsByDomainAction::class)->execute($user);
        }
    }
}
