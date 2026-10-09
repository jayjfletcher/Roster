<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Sso\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;
use RefactorCircus\Roster\Domains\Sso\Models\SsoConnectionModel;

/**
 * An SSO sign-in was refused.
 */
final class SsoLoginFailedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public SsoConnectionModel $connection,
        public ?string $email,
        public string $reason,
    ) {}
}
