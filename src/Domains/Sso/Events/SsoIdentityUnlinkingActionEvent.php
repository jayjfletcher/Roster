<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Sso\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;
use RefactorCircus\Roster\Domains\Sso\Models\SsoIdentityModel;

/**
 * An identity is about to be unlinked.
 */
final class SsoIdentityUnlinkingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public SsoIdentityModel $identity,
    ) {}
}
