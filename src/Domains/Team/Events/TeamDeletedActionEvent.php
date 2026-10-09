<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Team\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;

/**
 * A team has been deleted.
 */
final class TeamDeletedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public TeamModel $team,
    ) {}
}
