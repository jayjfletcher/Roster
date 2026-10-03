<?php

declare(strict_types=1);

namespace JayI\Roster\Domains\Invitation\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JayI\Roster\Contracts\ActionStartingEvent;
use JayI\Roster\Domains\Organization\Models\OrganizationModel;

/**
 * An invitation is about to be sent.
 */
final class InvitationCreatingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public OrganizationModel $organization,
        /** @var array<string, mixed> */
        public array $data,
    ) {}
}
