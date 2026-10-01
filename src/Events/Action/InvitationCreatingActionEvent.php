<?php

declare(strict_types=1);

namespace JayI\Roster\Events\Action;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Roster\Contracts\ActionStartingEvent;
use JayI\Roster\Models\Organization;

/**
 * An invitation is about to be sent.
 */
final class InvitationCreatingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Organization $organization,
        /** @var array<string, mixed> */
        public array $data,
    ) {}
}
