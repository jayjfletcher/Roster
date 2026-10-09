<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;

/**
 * A user's current organization and team are about to change.
 */
final class ContextSwitchingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Model $user,
        /** @var array<string, mixed> */
        public array $data,
    ) {}
}
