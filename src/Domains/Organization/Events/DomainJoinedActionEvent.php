<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;

/**
 * A user has joined organizations by email domain.
 */
final class DomainJoinedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Model $user,
        /** @var array<int, string> */
        public array $organizations,
    ) {}
}
