<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Role\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Roster\Contracts\ActionFinishedEvent;
use JayI\Roster\Domains\Role\Models\RoleAssignmentModel;

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
