<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Team\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Foundation\Contracts\ActionFinishedEvent;
use JayI\Roster\Domains\Team\Models\TeamModel;

/**
 * A team has been updated.
 */
final class TeamUpdatedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public TeamModel $team,
    ) {}
}
