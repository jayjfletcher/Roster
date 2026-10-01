<?php

declare(strict_types=1);

namespace JayI\Roster\Events\Action;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Roster\Contracts\ActionStartingEvent;
use JayI\Roster\Models\Impersonation;

/**
 * An impersonation is about to end.
 */
final class ImpersonationStoppingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Impersonation $impersonation,
        /** @var array<string, mixed> */
        public array $data,
    ) {}
}
