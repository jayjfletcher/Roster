<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Impersonation\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Foundation\Contracts\ActionStartingEvent;
use JayI\Roster\Domains\Impersonation\Models\ImpersonationModel;

/**
 * An impersonator is about to become the user.
 */
final class ImpersonationEnteringActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public ImpersonationModel $impersonation,
    ) {}
}
