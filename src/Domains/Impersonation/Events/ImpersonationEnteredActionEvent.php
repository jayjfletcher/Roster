<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Impersonation\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;
use RefactorCircus\Roster\Domains\Impersonation\Models\ImpersonationModel;

/**
 * An impersonator has become the user.
 */
final class ImpersonationEnteredActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public ImpersonationModel $impersonation,
    ) {}
}
