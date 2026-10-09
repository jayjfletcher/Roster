<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Permission\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;

/**
 * A user's effective permissions have been listed.
 */
final class UserPermissionsListedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Model $user,
        /** @var array<string, mixed> */
        public array $filters,
    ) {}
}
