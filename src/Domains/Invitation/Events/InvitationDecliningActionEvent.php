<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Invitation\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;
use RefactorCircus\Roster\Domains\Invitation\Models\InvitationModel;

/**
 * An invitation is about to be declined.
 */
final class InvitationDecliningActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public InvitationModel $invitation,
        public Model $user,
    ) {}
}
