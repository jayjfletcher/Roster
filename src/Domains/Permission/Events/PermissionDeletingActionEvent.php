<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Permission\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Foundation\Contracts\ActionStartingEvent;
use JayI\Roster\Domains\Permission\Models\PermissionModel;

/**
 * A permission is about to be deleted.
 */
final class PermissionDeletingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public PermissionModel $permission,
    ) {}
}
