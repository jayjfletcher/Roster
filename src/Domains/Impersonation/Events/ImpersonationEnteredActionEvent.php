<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Impersonation\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Roster\Contracts\ActionFinishedEvent;
use JayI\Roster\Domains\Impersonation\Models\ImpersonationModel;

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
