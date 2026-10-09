<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Role\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;
use RefactorCircus\Roster\Domains\Role\Models\RoleAssignmentModel;

/**
 * A role assignment has been revoked.
 */
final class RoleRevokedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public RoleAssignmentModel $assignment,
    ) {}
}
