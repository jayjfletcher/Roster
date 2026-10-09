<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Permission\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;
use RefactorCircus\Roster\Domains\Permission\Models\PermissionModel;

/**
 * A permission has been updated.
 */
final class PermissionUpdatedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public PermissionModel $permission,
    ) {}
}
