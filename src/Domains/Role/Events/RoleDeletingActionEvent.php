<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Role\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Roster\Contracts\ActionStartingEvent;
use JayI\Roster\Domains\Role\Models\RoleModel;

/**
 * A role is about to be deleted.
 */
final class RoleDeletingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public RoleModel $role,
    ) {}
}
