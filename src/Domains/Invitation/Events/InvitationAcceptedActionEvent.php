<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Invitation\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Roster\Contracts\ActionFinishedEvent;
use JayI\Roster\Domains\Invitation\Models\InvitationModel;

/**
 * An invitation has been accepted.
 */
final class InvitationAcceptedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public InvitationModel $invitation,
        public Model $user,
    ) {}
}
