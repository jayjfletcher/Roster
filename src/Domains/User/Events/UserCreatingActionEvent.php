<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;

/**
 * A user is about to be created.
 */
final class UserCreatingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        /** @var array<string, mixed> */
        public array $data,
    ) {}
}
