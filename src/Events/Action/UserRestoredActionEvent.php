<?php

declare(strict_types=1);

namespace JayI\Roster\Events\Action;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Roster\Contracts\ActionFinishedEvent;

/**
 * A deleted user has been restored.
 */
final class UserRestoredActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Model $user,
    ) {}
}
