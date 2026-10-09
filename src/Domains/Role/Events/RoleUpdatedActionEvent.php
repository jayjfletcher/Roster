<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Role\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;
use RefactorCircus\Roster\Domains\Role\Models\RoleModel;

/**
 * A role has been updated.
 */
final class RoleUpdatedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public RoleModel $role,
    ) {}
}
