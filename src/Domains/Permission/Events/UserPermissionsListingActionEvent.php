<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Permission\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;

/**
 * A user's effective permissions are about to be listed.
 */
final class UserPermissionsListingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Model $user,
        /** @var array<string, mixed> */
        public array $filters,
    ) {}
}
