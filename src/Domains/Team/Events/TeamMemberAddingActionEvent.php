<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Team\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;

/**
 * A member is about to join a team.
 */
final class TeamMemberAddingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public TeamModel $team,
        public Model $user,
    ) {}
}
