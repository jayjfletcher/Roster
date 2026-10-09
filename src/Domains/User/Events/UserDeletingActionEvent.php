<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;

/**
 * A user is about to be deleted.
 */
final class UserDeletingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Model $user,
    ) {}
}
