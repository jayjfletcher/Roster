<?php

declare(strict_types=1);

namespace JayI\Roster\Events\Action;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Roster\Contracts\ActionStartingEvent;

/**
 * A pending user is about to be approved.
 */
final class UserApprovingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Model $user,
    ) {}
}
