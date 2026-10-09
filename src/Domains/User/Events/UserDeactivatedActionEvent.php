<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;

/**
 * A user has been deactivated.
 */
final class UserDeactivatedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Model $user,
        public ?string $reason,
    ) {}
}
