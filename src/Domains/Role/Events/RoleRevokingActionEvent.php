<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Role\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;
use RefactorCircus\Roster\Domains\Role\Models\RoleAssignmentModel;

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
