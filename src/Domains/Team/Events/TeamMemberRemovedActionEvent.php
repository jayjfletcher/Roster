<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Team\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;

/**
 * A member has left a team.
 */
final class TeamMemberRemovedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public TeamModel $team,
        public Model $user,
    ) {}
}
