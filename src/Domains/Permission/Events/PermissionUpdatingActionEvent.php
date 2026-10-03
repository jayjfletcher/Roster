<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Permission\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Roster\Contracts\ActionStartingEvent;
use JayI\Roster\Domains\Permission\Models\PermissionModel;

/**
 * A permission is about to be updated.
 */
final class PermissionUpdatingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public PermissionModel $permission,
        /** @var array<string, mixed> */
        public array $data,
    ) {}
}
