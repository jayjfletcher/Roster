<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Role\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Roster\Contracts\ActionStartingEvent;
use JayI\Roster\Domains\Role\Models\RoleAssignmentModel;

/**
 * A role assignment is about to be revoked.
 */
final class RoleRevokingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public RoleAssignmentModel $assignment,
    ) {}
}
